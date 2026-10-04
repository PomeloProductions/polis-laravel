# Plugin Composition & Dependency Architecture

**Status:** Design (not implementation). A sibling workstream is building the
foundation — controller plugin submission, frontend spaces, and the first
Feedback plugin — concurrently. This document defines the layer that sits *on
top* of that foundation: how plugins depend on each other, how a dependency
resolver turns a chosen set into a coherent graph, and how a named
**Application** composition of plugins is resolved, provisioned, and operated.

## North star

> The root is the **Polis framework**. Every end-user **application is a
> composition of plugins**. Plugins **declare dependencies on other plugins**
> (like Composer/npm packages do). Someone **explores a plugin library and
> assembles an entire application** from it.

Today an "app" (HighScoresCenter, PolisOS, Card-Collecting) is a bespoke
Laravel+React consumer of the Polis packages, deployed as one monolithic image
per tenant through the controller. The end state inverts that: the consumer
shell becomes thin, and *features* are plugins — self-contained, versioned,
dependency-aware containers plus frontend bundles — that are composed into an
Application. This doc describes the composition and dependency layer that makes
that inversion tractable.

---

## 0. What already exists (the ground we build on)

This design extends, and does not replace, the merged foundation. Everything
below is a fact about the current code, verified against the repos.

### Backend plugin contract — `polis-laravel` #80 (merged)

`Polis\Plugins\PluginContract` is the backend plugin identity + registration
surface:

```php
interface PluginContract {
    public function key(): string;          // stable machine id, e.g. "todo"
    public function name(): string;         // display name
    public function version(): string;      // semver, for frontend/backend parity
    public function capabilities(): array;  // advisory labels, e.g. ['routes','entity:task']
    public function register(PluginRegistrar $registrar): void;
}
```

`Polis\Plugins\PluginRegistrar` is the fluent, declaration-only capability
surface a plugin calls inside `register()`:
`bindings()`, `listeners()`, `observers()`, `routes()`, `migrations()`,
`config()`, `validators()`, `policies()`, and `entityType()` (a documented STUB
awaiting the 0.3 entity-type registry).

`Polis\Plugins\PluginManager` discovers plugins from `config('plugins.enabled')`
(an ordered array of class strings), instantiates each through the container,
calls `register()` once, and aggregates/de-duplicates by `key()` (first wins,
config order deterministic). It is the single point that touches the
container/router/dispatcher, and it already emits a lightweight **manifest**:

```php
// PluginManager::manifest()
[ ['key'=>..., 'name'=>..., 'version'=>..., 'capabilities'=>[...]], ... ]
```

Key property: **enabling a plugin is config, not hand-wiring.** A composition
is, at the backend layer, "which plugin classes are in `plugins.enabled`, in
what order, with what `config('plugin.<key>.*')`."

### Frontend plugin contract — `polis-react` #99 (merged)

`definePlugin(def)` (identity/typing passthrough) and `registerPlugin(def)`
wire a `PluginDefinition` — `{ key, name?, version?, components, pageTypes,
navItems, routes, settingsTabs, reduxSlices }` — into frontend registries.
Plugins inject into **five customizable spaces**: `components`, `pageTypes`,
`navItems`+`routes`, `settingsTabs`, `reduxSlices`. Registration is idempotent
per `key`; getters return `order`-sorted merged arrays. The frontend `key`
**must equal** the backend `key` — this is the identity bridge across the two
halves.

### Current build direction (the foundation being built now)

Each plugin ships as a **manifest** of the shape:

```jsonc
{
  "name": "Feedback",
  "slug": "feedback",
  "version": "1.0.0",
  "image": "ghcr.io/pomeloproductions/polis-plugin-feedback:sha-...",
  "frontend": [
    { "bundle": "feedback.js", "space": "navItems", "component": "FeedbackNav" }
  ],
  "config_schema": { /* JSON Schema for per-install config */ }
}
```

The Docker image is submitted **through the controller**, reusing the existing
`ImagePush → Registry → Deploy` pipeline (the same path tenant app images take
today). Frontend deliverables (`frontend[]`) drop built bundles into the app's
customizable **spaces**. Repo-per-plugin; `polis-plugin-feedback` already exists.

### Controller / tenant model (client-driver — the platform controller)

- client-driver **is** the platform controller. Tenants hold a URL + bearer
  token; image upload, deploy, and SSE logs all go through its HTTPS API.
- Deploy target is resolved from an **org-owned `Service` record by ID**, not
  the legacy `{app}-slug` name convention
  ([[project_deploy_by_service_id]]).
- The controller provisions every tenant: namespace, DB, ingress, deployment,
  image ([[project_controller_provisions_all_tenants]]). TF manages only cluster
  infra + the controller itself.
- The Helm chart exposes **generic, runtime-agnostic primitives**:
  `extraServices`, `extraWorkers`, `extraIngressRoutes`. Framework specifics
  (queue:work, Reverb) are config layered on top, never baked into the chart
  ([[feedback_chart_generic_primitives]]).

These four facts are load-bearing for §4: a plugin container is **just another
Service**, and the chart already has the primitive (`extraServices`) to run it.

---

## 1. Manifest `dependencies`

Add one field to the plugin manifest. A plugin declares the *other plugins* it
needs, each with a semver constraint — exactly the Composer/npm mental model.

```jsonc
{
  "name": "Leaderboard",
  "slug": "leaderboard",
  "version": "2.3.1",
  "image": "ghcr.io/pomeloproductions/polis-plugin-leaderboard:sha-...",

  "dependencies": [
    { "plugin": "scoring",  "constraint": "^1.4" },
    { "plugin": "entities", "constraint": ">=0.3 <1.0" }
  ],
  "peerDependencies": [
    { "plugin": "notifications", "constraint": "^2" }
  ],
  "provides": [ "entity:score", "capability:ranking" ],

  "frontend": [ /* bundle → space → component, as today */ ],
  "config_schema": { /* JSON Schema */ }
}
```

Field semantics:

- **`dependencies[]`** — hard requirements. `plugin` is the depended-on plugin's
  **`slug`** (the `key()` identity shared across backend + frontend). `constraint`
  is a semver range using the Composer/npm grammar subset: `^`, `~`, `>=`, `<`,
  `-` ranges, `*`, and `||` union. Resolved against the depended-on plugin's
  **`version`**. A dependency's own `dependencies[]` are pulled in transitively.
- **`peerDependencies[]`** — "must be present in the Application and satisfy the
  range, but I do not pull it in." Used when two plugins must agree on a shared
  contract version (e.g. everyone that emits notifications agrees on the
  `notifications` plugin major) without each copy dragging in its own. Mirrors
  npm peer deps; surfaced as a *warning→error* if unmet (see §2 failure modes).
- **`provides[]`** — optional **virtual capabilities / entity types** a plugin
  satisfies, so a dependency can target `entity:score` abstractly and any plugin
  that `provides` it can fulfill the slot. This is the bridge to the 0.3
  entity-type generalization (§6): `provides: ["entity:*"]` is how a plugin
  announces it contributes an entity type the resolver can match a dependency
  against. Virtual capabilities have no version of their own; they inherit the
  version of the providing plugin for constraint purposes.

**Why slug, not image:** dependencies bind to plugin *identity + version range*,
never to a specific image digest. The resolver (§2) selects the concrete
version — and therefore the concrete image — that satisfies all constraints,
exactly like a lockfile resolves a range to one package version.

**Registry metadata, not runtime code.** `dependencies` lives in the manifest
(registry/catalog metadata consumed by the controller + resolver). It is NOT a
new method on `PluginContract` — the backend contract stays a pure capability
surface. The link between them is the shared `key()`/`slug`: the resolver works
in manifest-space, and the result is projected into `config('plugins.enabled')`
(backend) and the frontend registry (frontend) as an *ordered* list.

---

## 2. Dependency resolver

Given a user-chosen **root set** of plugins (each pinned to a version or a
range), produce a **resolution**: the full transitive set of plugins, each at
exactly one version, that satisfies every constraint — or a structured failure.
This is a SAT-ish version solver in the Composer/npm/cargo family. It runs in
the controller, server-side, on an Application's catalog of manifests.

### Inputs

- **Root requirements** — the Application's directly-added plugins:
  `[{ plugin, constraint }]`.
- **Catalog** — all available `(slug, version) → manifest` tuples from the
  plugin registry (internal library + any enabled 3rd-party sources).
- **Existing lock** (on upgrade) — the previous resolution, used to minimise
  churn (prefer already-locked versions when still valid).

### Output

A **lockfile** — the deterministic artifact an Application is provisioned from:

```jsonc
{
  "application": "high-scores-center",
  "resolvedAt": "2026-10-04T...Z",
  "root": [ { "plugin": "leaderboard", "constraint": "^2.3" } ],
  "resolved": [
    { "slug": "entities",    "version": "0.9.2", "image": "ghcr.io/.../entities:sha-aaa",    "reason": "dep of leaderboard,scoring" },
    { "slug": "scoring",     "version": "1.4.7", "image": "ghcr.io/.../scoring:sha-bbb",     "reason": "dep of leaderboard" },
    { "slug": "leaderboard", "version": "2.3.1", "image": "ghcr.io/.../leaderboard:sha-ccc", "reason": "root" }
  ],
  "order": ["entities","scoring","leaderboard"],  // topological: deps before dependents
  "graphHash": "sha256:..."
}
```

`order` is the topological sort used to populate `config('plugins.enabled')`
(backend determinism = install order) and to sequence container provisioning.

### Algorithm

A backtracking **version solver** (PubGrub-lite / Composer-style). Pseudocode:

```
resolve(rootReqs, catalog, lock?):
  1. BUILD working set = rootReqs (as constraints keyed by slug)
     seed preferred versions from lock? where still in-range
  2. WORKLIST loop until all constraints have a chosen version:
     a. pick the slug with the FEWEST candidate versions satisfying its
        accumulated constraint  (fail-fast / most-constrained-first)
     b. candidates = versions of slug in catalog matching the constraint,
        sorted HIGHEST-first (newest preferred), with a locked version floated
        to the front if still valid (minimal-churn on upgrade)
     c. for each candidate (backtracking point):
          - tentatively choose it
          - FOLD IN its dependencies[] + peerDependencies[] as new/added
            constraints (intersect with any existing constraint for that slug)
          - if any slug's intersected constraint becomes unsatisfiable
            (empty candidate set) -> this candidate is a dead end, record the
            CONFLICT CAUSE, backtrack to next candidate
     d. if no candidate works, backtrack to the prior decision; if none
        remains, FAIL with the recorded conflict chain
  3. CYCLE CHECK: build the directed dep graph of chosen versions; run DFS;
     a back-edge = dependency cycle -> FAIL with the cycle path
  4. PEER CHECK: every peerDependency must map to a chosen version in range;
     unmet peer -> FAIL (or WARN in permissive mode)
  5. PROVIDES RESOLUTION: a dependency on a virtual capability (entity:score)
     is satisfied if >=1 chosen plugin provides[] it; if >1 provider and the
     slot is single-valued, FAIL ambiguous-provider (user disambiguates)
  6. TOPO SORT chosen graph -> `order`; emit lockfile with graphHash
```

Complexity is worst-case exponential (version SAT is NP-hard) but near-linear in
practice because the catalog is small, constraints are tight, and
most-constrained-first prunes hard. Add a **decision budget** (max backtracks);
exceeding it returns `RESOLUTION_TIMEOUT` with the partial conflict set rather
than hanging.

### Failure modes (all structured, all surfaced to the library UX in §5)

| Code | Cause | What the user sees |
|------|-------|--------------------|
| `VERSION_CONFLICT` | Two plugins require incompatible ranges of a third (`scoring@^1` vs `scoring@^2`) | The conflict chain: who asked for what, which pair is irreconcilable |
| `MISSING_PLUGIN` | A required slug has no version in the catalog | "`leaderboard` needs `scoring`, not in the library" |
| `NO_SATISFYING_VERSION` | Slug exists but no version matches the intersected constraint | The intersected range + available versions |
| `DEPENDENCY_CYCLE` | A → B → A in the chosen graph | The cycle path |
| `UNMET_PEER` | A peerDependency isn't present / out of range | "add `notifications@^2` to this Application" |
| `AMBIGUOUS_PROVIDER` | >1 plugin `provides` a single-valued virtual slot | The candidate providers; user picks one |
| `RESOLUTION_TIMEOUT` | Decision budget exceeded | Partial conflict set + suggestion to pin |

The resolver is **pure and side-effect free**: inputs → lockfile|failure. It
MUST be unit-testable with a synthetic in-memory catalog (per
[[feedback_test_standards]] — pure/no-DB unit tests for the solver core;
integration tests against the real registry). Determinism is a hard requirement:
same inputs → byte-identical `graphHash`.

---

## 3. The `Application` entity

An **Application** is a named composition: an ordered set of plugins + versions
+ per-plugin config that **resolves its dependency graph into a runnable set of
plugin containers + frontend space assignments + routing**. It is the new unit
that an org actually runs — the thing HSC/PolisOS become.

### Shape

An Application is a controller-side record (one row + children), owned by an org,
backed by exactly one tenant `Service` surface (§4). Conceptual schema:

```jsonc
{
  "id": 42,
  "org_id": 1,
  "name": "High Scores Center",
  "slug": "high-scores-center",
  "service_id": 128,                 // the tenant Service it provisions into

  "plugins": [                       // the ROOT set (what the user added)
    {
      "slug": "leaderboard",
      "constraint": "^2.3",
      "enabled": true,
      "config": { /* validated against the plugin's config_schema */ },
      "spaces": { "navItems": ["FeedbackNav"] }  // optional space pinning/override
    }
  ],

  "lock": { /* the resolver output from §2, regenerated on change */ },
  "state": "running",                // see lifecycle
  "shell": { "frontend": "polis-react@x", "backend": "polis-laravel@y" }
}
```

- **`plugins[]` is the root set**, not the resolved set. Transitive deps live in
  `lock`. The user curates roots; the resolver owns the rest.
- **`config`** per plugin is validated against that plugin's `config_schema`
  (JSON Schema) at edit time — bad config fails before provisioning.
- **`spaces`** lets the Application place a plugin's frontend deliverables into
  specific customizable spaces (or accept the manifest defaults). This is where
  "frontend deliverables into customizable spaces" becomes per-Application
  layout.
- **`shell`** pins the Polis framework versions the composition runs against —
  the framework is the root of the tree, and the Application declares which
  framework it composes onto.

### Lifecycle

```
                 add/remove plugins, edit config
create ──► draft ───────────────┐
                                ▼
                 ┌──────────► resolve ──(fail)──► conflict (§2) ──► back to draft
                 │               │(ok → lock)
                 │               ▼
                 │           resolved ──► provision ──► running
                 │                                        │
          upgrade│                                        │ enable / disable plugin
                 └────────────────────────────────────────┘
                                                          ▼
                                                      (teardown keeps DB — §no-delete)
```

1. **create / draft** — name it, pick a framework shell version. Empty
   composition is valid.
2. **resolve** — run §2 over the root set → `lock` or a structured conflict.
   Non-destructive; a draft can be re-resolved freely.
3. **provision** — §4. Turn the lock into running containers + spaces + routing.
4. **enable / disable** — toggle a root plugin without removing it; disabling
   recomputes the lock (a dependency only kept alive by a disabled plugin is
   pruned) and reconciles containers.
5. **upgrade** — widen/bump a root constraint, re-resolve with
   minimal-churn-from-lock (§2 step 2b), diff old lock vs new lock, and roll
   containers forward. Migrations run per plugin via the existing `migrations()`
   registrar path; order follows the lock's topo `order`.
6. **teardown** — remove containers/ingress but **never delete the DB**
   ([[feedback_no_database_delete_capability]]); deprovision retains data.

The Application is the single source of truth; `config('plugins.enabled')` and
the frontend registry are **projections** of its lock, regenerated on every
resolve — never hand-edited.

---

## 4. Controller provisioning of a composed app

Provisioning turns a resolved Application (its lock) into running infrastructure,
**reusing the existing tenant/Service/Deploy pipeline and the chart's generic
`extraServices` primitive** — no new deployment mechanism.

### Mapping: lock → infrastructure

```
Application(service_id=128)  ─┐
                              │   tenant Service surface (namespace, DB, ingress,
                              │   the framework "shell" deployment: polis-react +
                              │   polis-laravel)  ── provisioned as today
  lock.resolved[]:           │
    entities@0.9.2    ───────┼──► extraServices[] entry  (container: image sha-aaa)
    scoring@1.4.7     ───────┼──► extraServices[] entry  (container: image sha-bbb)
    leaderboard@2.3.1 ───────┘──► extraServices[] entry  (container: image sha-ccc)
```

Each resolved plugin becomes **one `extraServices` entry** in the chart values
for that tenant — a plugin is, infrastructurally, just another Service in the
namespace. The controller already knows how to: push an image to the registry,
resolve a deploy target by Service ID, and deploy with a SHA-pinned image +
`pullPolicy: Always` ([[feedback_image_pull_policy]]). Plugin containers inherit
that path verbatim. No live cluster edits — the controller renders chart values
and goes through the normal deploy ([[feedback_no_live_cluster_edits]]).

### Steps (controller-side, idempotent reconcile)

1. **Ensure images** — for each `lock.resolved[].image`, confirm the SHA exists
   in the registry (it was submitted through the controller's
   `ImagePush → Registry` path when the plugin was published). No rebuild at
   provision time; provision consumes already-published digests.
2. **Render `extraServices`** — one entry per resolved plugin: image, env
   (plugin `config` injected as namespaced env/secret), any `extraWorkers` the
   plugin declares (queue workers are config on the generic primitive, not baked
   in — [[feedback_chart_generic_primitives]]).
3. **Wire frontend spaces** — the shell's `polis-react` build loads each
   plugin's `frontend[]` bundles into the spaces the Application assigned, in the
   lock's topo order (so a dependency's components register before a dependent's).
   Frontend bundles are static deliverables served by the shell; they don't each
   need a container.
4. **Routing** — the backend `routes()` groups each plugin registers (prefix +
   middleware) are exposed through the shell's ingress; inter-service routes to
   plugin containers go through `extraIngressRoutes` when a plugin's container
   must be reachable directly.
5. **Migrations** — run in topo order; each plugin's `migrations()` directory is
   already auto-loaded by `PluginManager::applyMigrations()`.
6. **Deploy** — one deploy through the existing pipeline; verify the cluster, not
   the Actions red (the SSE stream times out at 900s but the deploy succeeds —
   [[reference_deploy_sse_false_failure]]).

### Inter-plugin communication (transport OPEN — options, no decision)

Plugins communicate over the **interfaces they declare**, not ad-hoc. The
*contract* is defined (a plugin `provides`/depends-on capabilities; §1), but the
**transport is explicitly left open**:

- **In-process (same image).** If the composition is packed into the shell image
  (plugins as composer/npm deps of the shell), communication is the existing
  `PluginManager` aggregation — direct container bindings, events, observers. No
  network. Simplest; loses per-plugin isolation + independent scaling.
- **Per-plugin container + sync HTTP.** Each plugin a Service; calls over
  cluster-internal HTTP against the plugin's declared `routes()`. Isolated,
  independently deployable; adds latency + needs a service-discovery convention.
- **Per-plugin container + async/event bus.** Shared redis
  ([[project_shared_redis_replication]]) / queue; plugins emit + subscribe to
  declared events. Decoupled; harder to reason about ordering.
- **Hybrid** — in-process for tightly-coupled plugins (same `provides` family),
  network for the rest; the resolver could even *co-locate* plugins that share a
  hard dependency.

Recommendation to revisit once the foundation lands: start **in-process** (ship
the whole composition as one shell image built from the locked plugin set) to
prove the resolver + Application model end-to-end, then graduate hot/large/3rd-
party plugins to their own containers via `extraServices` without changing the
contract. This decision is deferred, not made here.

---

## 5. Library-to-build-an-app UX

The user-facing loop that makes "assemble an application from a library" real.
Lives in the controller UI (client-driver web) alongside the existing
tenant/Service management.

```
┌─ Plugin Library ──────────────┐     ┌─ Application: "High Scores Center" ─┐
│ search / filter / categories  │     │ Root plugins:                        │
│ ┌───────────┐ ┌───────────┐   │ add │   • leaderboard  ^2.3   [config][x] │
│ │ Leaderbd  │ │ Scoring   │ ──┼────►│   • feedback     ^1     [config][x] │
│ │ ★ v2.3.1  │ │ ★ v1.4.7  │   │     │                                      │
│ │ internal  │ │ internal  │   │     │ Resolved graph (lock):               │
│ └───────────┘ └───────────┘   │     │   entities 0.9.2 ← scoring,leaderbd  │
│ ┌───────────┐                 │     │   scoring  1.4.7 ← leaderboard       │
│ │ Payments  │  3rd-party ⚠    │     │   leaderbd 2.3.1 ← root              │
│ └───────────┘                 │     │ [Resolve]  → ✓ / conflict panel      │
└───────────────────────────────┘     │ [Provision] (enabled when resolved)  │
                                       └──────────────────────────────────────┘
```

Flow:

1. **Browse the catalog** — plugins from the registry: name, version, `provides`,
   source (internal vs 3rd-party), `config_schema` preview, README.
2. **Add to Application** — adds a root requirement; default constraint `^<latest>`.
3. **Resolve** — run §2. On success, render the resolved graph (who pulled in
   what — the lock's `reason`). On failure, a **conflict panel** rendering the
   §2 failure code + chain, with suggested fixes ("pin scoring to ^1", "remove
   X", "pick a provider").
4. **Configure** — per-plugin config form generated from `config_schema`;
   validated live. Assign/accept frontend space placements.
5. **Provision** — §4; stream deploy status (verify cluster per
   [[reference_deploy_sse_false_failure]]).

### Internal-only vs 3rd-party security model (NOTE, not decided)

The catalog distinguishes **first-party** (Pomelo-published, trusted, built
through our `ImagePush` pipeline) from **3rd-party** plugins. Open questions to
settle before 3rd-party plugins are allowed:

- **Image provenance / signing** — do 3rd-party images get signed + verified
  before the controller will deploy them? (They already must flow through the
  controller's registry path, which is a natural chokepoint.)
- **Capability sandboxing** — a 3rd-party plugin gets `bindings()`,
  `policies()`, `migrations()`, routes, DB access. In-process composition
  (§4 option 1) gives it the whole app; container isolation (option 2/3) bounds
  it to its own Service + declared interfaces. The transport decision in §4 and
  the 3rd-party trust decision are coupled.
- **Review/approval gate** — likely a human approval before a 3rd-party plugin
  enters the library, mirroring the plugin system's existing "large needs
  approval" posture ([[project_polis_plugin_system]]).
- **Config secrets** — plugin `config` may hold secrets; these must land as
  namespaced k8s secrets, never in the Application record in plaintext.

Recommendation: **ship internal-only first** (first-party library, in-process or
first-party containers), defer the 3rd-party trust boundary until the resolver +
Application + container isolation are proven. Noted, not decided.

---

## 6. Migration path — bespoke app → composition

HSC and PolisOS become plugin compositions **incrementally**, never big-bang.
The strategy: the bespoke consumer stays the "shell," and features are carved out
into plugins one at a time, each verified in isolation before the next.

### Order of extraction (lowest-risk first)

1. **Already-moving features become the pilot.** PolisOS's Todo / time-management
   is already being *de-extracted from the shared packages into PolisOS*
   ([[project_todo_deextraction]]). That same code is the natural **first
   plugin**: instead of landing in the PolisOS app, it lands in a
   `polis-plugin-todo` with a manifest + `dependencies`. PolisOS's shell then
   *composes* it. This reuses in-flight work rather than inventing a greenfield
   pilot.
2. **Feedback plugin (already being built) is the reference container.** The
   first container-delivered plugin ([[project_polis_plugin_system]]) proves the
   controller-submission + spaces path end-to-end; HSC/PolisOS adopt it as their
   first *external* plugin, exercising `extraServices` provisioning (§4) before
   any of their own code is extracted.
3. **Leaf features next** — self-contained features with no inbound deps
   (notifications, a reports tab). Each: extract to `polis-plugin-<x>`, add a
   manifest, declare `dependencies` on the framework-provided entity types,
   publish through the controller, add to the Application's root set.
4. **Core domain last** — the app's central entity logic (scores for HSC) moves
   only after the entity-type substrate exists.

### Hard dependency on the 0.3 entity-type generalization

This is the gating prerequisite and must be called out plainly. The
`entityType()` registrar method is a **documented STUB today** — recorded but not
consumed (PR #80). The Polis 0.3 roadmap generalizes
UserPage/UserPageComponent/Contact/ArticleNote/Thread/ExternalAccountConnection
to an `IsAnEntityContract` ([[project_polis_03_roadmap]]). Composition needs this
because:

- Plugins depend on **entity types**, not on each other's internals. `leaderboard`
  depends on `entity:score` (a `provides` virtual capability, §1), which some
  plugin supplies. Without a live entity-type registry, cross-plugin data
  contracts have nothing stable to bind to and plugins would have to reach into
  each other's models — breaking isolation.
- The resolver's `provides`/`entity:*` matching (§2 step 5) is only meaningful
  once entity types are first-class and version-able.

**Therefore:** the composition layer's *dependency resolution on entity types*
is **blocked on 0.3 shipping the entity-type registry** that consumes
`PluginManager::entityTypes()`. Plugin-to-plugin *version* dependencies (§1/§2
for concrete slugs) do **not** need 0.3 and can land first. Sequence
accordingly (§7).

### Incremental cutover per feature

For each extracted feature: build the plugin → publish through controller →
add to the app's Application as a root plugin behind a flag → run both the old
in-shell code path and the plugin in parallel → verify parity → remove the
in-shell code. The Application's `enable/disable` lifecycle (§3) is the flag.

---

## 7. Build phases

Sequenced on top of the foundation being built now (controller submission +
spaces + Feedback plugin). Each phase is independently shippable and testable;
later phases assume earlier ones.

### Phase 0 — Foundation (in flight, NOT this work)
Controller plugin submission (`ImagePush → Registry → Deploy` for a plugin
image), frontend spaces loading `frontend[]` bundles, the Feedback plugin as the
first container plugin. *This design assumes Phase 0 lands.*

### Phase 1 — Manifest `dependencies` + catalog
Add `dependencies` / `peerDependencies` / `provides` to the manifest schema.
Stand up the **plugin registry/catalog** the controller reads
`(slug, version) → manifest` from. No resolution yet — just storing and serving
dependency metadata. *Deliverable: catalog API + manifest validation.*

### Phase 2 — Resolver (pure core)
Implement §2 as a **pure, unit-tested** version solver against an in-memory
catalog (per [[feedback_test_standards]]: pure/no-DB unit tests; synthetic
fixtures for every failure mode + cycle + minimal-churn upgrade). Emit the
lockfile shape. No Application entity yet — resolver is callable standalone.
*Deliverable: deterministic resolver + full failure-mode test matrix.*

### Phase 3 — Application entity + lifecycle (controller)
Model the Application (root set, per-plugin config, lock, state) in the
controller DB, bound to a tenant Service. Wire create→draft→resolve, config
validation against `config_schema`. Resolve calls Phase 2. *No provisioning yet —
you can compose + resolve + see conflicts.*

### Phase 4 — Provisioning (lock → `extraServices`)
Render a resolved lock into chart `extraServices` + frontend space assignments +
routing + migrations, through the existing deploy pipeline (§4). Start
**in-process composition** (whole locked set as one shell image) to prove the
model, then enable per-plugin containers. Enable/disable + upgrade reconcile.
*Deliverable: a resolved Application that actually runs.*

### Phase 5 — Library UX
The browse→add→resolve→configure→provision loop (§5) in client-driver web,
including the conflict panel and `config_schema`-driven config forms. Internal-
only catalog. *Deliverable: assemble an app from the library, end to end.*

### Phase 6 — Entity-type dependencies (gated on 0.3)
Once 0.3's entity-type registry consumes `PluginManager::entityTypes()`, light up
`provides: ["entity:*"]` matching in the resolver (§2 step 5) and let plugins
depend on entity types abstractly. Unblocks core-domain extraction (§6 step 4).

### Phase 7 — 3rd-party trust boundary (deferred decision)
Image signing/verification, capability sandboxing, review gate, secret handling
(§5). Only after the internal model is proven. Coupled to the §4 transport
decision.

### Dependency graph of phases

```
P0 (foundation) ─► P1 (manifest+catalog) ─► P2 (resolver) ─► P3 (Application)
                                                                    │
                                                                    ▼
                                                    P4 (provision) ─► P5 (library UX)
P-0.3 (entity registry) ─────────────────────────────────────────► P6 (entity deps)
                                                                    P7 (3rd-party)  [deferred]
```

---

## Open questions (explicitly deferred, not decided here)

1. **Inter-plugin transport** — in-process vs sync HTTP vs event bus vs hybrid
   (§4). Recommend starting in-process; decide at Phase 4.
2. **3rd-party trust boundary** — signing, sandboxing, approval (§5/P7).
3. **Co-location policy** — may the resolver pack hard-dependent plugins into one
   container for latency? (Couples to transport.)
4. **Whether `dependencies` ever becomes a `PluginContract` method** — kept as
   manifest-only metadata here; revisit if backend code ever needs it at runtime.
5. **Lock storage + GitOps** — does the Application lock live only in the
   controller DB, or is it also committed (kubernetes-cluster-style auto-bump)?
   Couples to [[feedback_never_manual_image_bumps]].

---

*Design doc. Companion to the Phase-0 foundation. Backend contract: `polis-laravel`
#80. Frontend contract: `polis-react` #99. Builds on the controller's existing
tenant/Service/Deploy model in client-driver.*
