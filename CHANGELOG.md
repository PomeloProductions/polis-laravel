# Changelog

## Unreleased

### Bug Fixes

* **dashboard:** map expired/invalid JWTs to an explicit 401 in the exception handler, placed before the generic JWTException case so the 401 contract is regression-proof against future reordering of the switch
* **dashboard:** allow `expand[user]` on the organization-managers index request so the dashboard can list managers alongside their user in a single call (previously any expand threw an AuthorizationException / 403)

## [0.5.0](https://github.com/PomeloProductions/polis-laravel/compare/v0.4.0...v0.5.0) (2026-08-19)


### Miscellaneous Chores

* release 0.5.0 ([36d789c](https://github.com/PomeloProductions/polis-laravel/commit/36d789c1cafb621323eef248e65ecf3d35df7325))
* release 0.5.0 (422 validation contract) ([5d03bb3](https://github.com/PomeloProductions/polis-laravel/commit/5d03bb3deb2657b771b6adb032b292218ee04009))

## [0.4.0](https://github.com/PomeloProductions/polis-laravel/compare/v0.3.0...v0.4.0) (2026-08-13)


### Features

* **policies:** fall back to package concrete policy when consumer omits App shim ([c6a1ac2](https://github.com/PomeloProductions/polis-laravel/commit/c6a1ac26e8f09552d6415fcc3bc1b49c0d146fac))
* **policies:** fall back to package concrete policy when no App override exists ([16843dd](https://github.com/PomeloProductions/polis-laravel/commit/16843ddfe69bacbf122804a40e9f91f0e4400f86))
* **requests:** resolve App request overrides so consumers can drop request shims ([0e9df38](https://github.com/PomeloProductions/polis-laravel/commit/0e9df380e01381b7215a678f2ea0c223c82169fc))
* **requests:** resolve App request overrides so consumers can drop request shims ([6eae52f](https://github.com/PomeloProductions/polis-laravel/commit/6eae52fef008656f973fc01e369e7a5298df9714))


### Bug Fixes

* **dashboard:** explicit JWT 401 mapping + allow organization-manager user expand ([70d444c](https://github.com/PomeloProductions/polis-laravel/commit/70d444cf004d02f9619a39dbe5571c62fb3c423a))
* **dashboard:** explicit JWT 401 mapping + allow organization-manager user expand + feature tests ([64e4d24](https://github.com/PomeloProductions/polis-laravel/commit/64e4d24dcdf5ef04d2cf6d90716923562e974d7f))
* **dashboard:** JWT 401 mapping + organization-manager user expand ([67f69f7](https://github.com/PomeloProductions/polis-laravel/commit/67f69f74fc00d1ab34443f34ba0b9cdd775cdb84))
* **dashboard:** map expired/invalid JWT to 401 + allow organization-manager user expand ([d2a4256](https://github.com/PomeloProductions/polis-laravel/commit/d2a42563a7e1b9c599fd34a79143649d1acefd95))
* **requests:** correct packageRequests() scan dir so override bindings register ([94e2b5b](https://github.com/PomeloProductions/polis-laravel/commit/94e2b5b25c8f8052e9ac5f04ce5606facb6eb24e))

## [0.3.0](https://github.com/PomeloProductions/polis-laravel/compare/v0.2.0...v0.3.0) (2026-08-07)


### Features

* **entity:** migrate Article + user-owned resources onto polymorphic entity owner ([45846f8](https://github.com/PomeloProductions/polis-laravel/commit/45846f8b2a6fd075754aeceea7810ddd40b767bd))
* **entity:** migrate Article + user-owned resources onto polymorphic entity owner ([ccb49ee](https://github.com/PomeloProductions/polis-laravel/commit/ccb49eee061c4ad5b53d6362d8e8ff56c92522ca))
* **entity:** reusable entity-scoped controller/policy layer; rebase Article onto it ([121739e](https://github.com/PomeloProductions/polis-laravel/commit/121739e8b3087a5f183537dad696f36fd43735a7))
* **entity:** reusable entity-scoped controller/policy layer; rebase Article onto it ([fbabf06](https://github.com/PomeloProductions/polis-laravel/commit/fbabf0641773849aae723b3acfc24a6fd221ce3f))
* **http:** RejectsUnknownParams trait + adopt in sample FormRequests ([ff3378e](https://github.com/PomeloProductions/polis-laravel/commit/ff3378e8fbd38100dd76fedaf8640c65e8d727dc))
* **http:** RejectsUnknownParams trait + adopt in sample FormRequests ([26378a0](https://github.com/PomeloProductions/polis-laravel/commit/26378a02d493ea6f50d00853c0137629eb767ee6))
* **org-detail:** expose role context on /me + org-scope Article contracts ([fadc7a5](https://github.com/PomeloProductions/polis-laravel/commit/fadc7a5ae15973da40209ca54db76a264e730228))
* **org-detail:** expose role context on /me and org-scope Article contracts ([fcda90f](https://github.com/PomeloProductions/polis-laravel/commit/fcda90f1493dca10847e1e63132d7b50fd56b850))
* **push:** migrate to laravel-notification-channels/fcm ([8bce40f](https://github.com/PomeloProductions/polis-laravel/commit/8bce40f8cc441a8fc32293301335f15cff496150))
* **push:** migrate to laravel-notification-channels/fcm for FCM v1 ([225dbae](https://github.com/PomeloProductions/polis-laravel/commit/225dbae514bce9194fef510bc65d59aa9e71d523))
* reusable org-member invite → email → accept/set-password flow ([1cb2a1f](https://github.com/PomeloProductions/polis-laravel/commit/1cb2a1fbd309c3581b4355efd3c480d60096eb3a))
* reusable org-member invite → email → accept/set-password flow ([2dd84bb](https://github.com/PomeloProductions/polis-laravel/commit/2dd84bb121cff2b8e40096f632bdef07b6fe612c))
* **todo:** extract generic period/node-tree framework + Todo module ([c3714a8](https://github.com/PomeloProductions/polis-laravel/commit/c3714a8a96510d30e021b52eb4f07c5a4b22f0e2))
* **todo:** generic period/node-tree framework + Todo module ([f6ad198](https://github.com/PomeloProductions/polis-laravel/commit/f6ad198674af26bded544fd3bd91fd00ca37b8c5))
* **traits:** add HasExternalSources trait + Source model + migration ([f9fda3d](https://github.com/PomeloProductions/polis-laravel/commit/f9fda3d21db02c7a5399044c3841fb4a4a85049b))
* **traits:** add HasExternalSources trait + Source model + migration ([0dcb33d](https://github.com/PomeloProductions/polis-laravel/commit/0dcb33d67bf73396c0dbe6b5f43793aa9d2aadc3))

## [0.2.0](https://github.com/PomeloProductions/polis-laravel/compare/v0.1.0...v0.2.0) (2026-06-09)


### Features

* add BaseModelCacheService + observer (from Lingwave) ([6a9e5e4](https://github.com/PomeloProductions/polis-laravel/commit/6a9e5e4d6523279098eae7f88044410133744036))
* add BaseModelCacheService + observer (from Lingwave) ([c901edb](https://github.com/PomeloProductions/polis-laravel/commit/c901edbfa1c90ceb810bdb9d73858c1c2e35c44a))
* add ExternalAccountConnection + ExternalRateLimiter (from Card-Collecting) ([568e964](https://github.com/PomeloProductions/polis-laravel/commit/568e964928875e66484f4b3f6ea271fd1b127794))
* add ExternalAccountConnection + ExternalRateLimiter (from Card-Collecting) ([adceb0f](https://github.com/PomeloProductions/polis-laravel/commit/adceb0fcc4ad526ccfa8396bfbcfcc7d290e118d))
* add NoopStripeCustomerService default binding ([81d7a62](https://github.com/PomeloProductions/polis-laravel/commit/81d7a62f864c9ff58fa57bdc5e465802253eeba7))
* add NoopStripeCustomerService default binding ([47ca415](https://github.com/PomeloProductions/polis-laravel/commit/47ca4157fbadd160662d549c6446581f035dc98b))
* add renewal_failure + membership_expired email templates; finish migrating hardcoded copy to template system ([6eeb07a](https://github.com/PomeloProductions/polis-laravel/commit/6eeb07aa2784121c22f09472c4a801a951b16bb6))
* add renewal_failure + membership_expired email templates; finish migrating hardcoded copy to template system ([dec6d52](https://github.com/PomeloProductions/polis-laravel/commit/dec6d5240da9895efcf22d164ee12be6460b286a))
* add runtime-editable email template system + move 4 listeners and 1 command ([7f43e4f](https://github.com/PomeloProductions/polis-laravel/commit/7f43e4f0ffaea809c8e4baae9ca910d3a17c365e))
* admin API for editing email + push templates ([ad1c1a6](https://github.com/PomeloProductions/polis-laravel/commit/ad1c1a6136b7c411e99cd86af0453fae60f7da08))
* admin API for editing email + push templates ([a599b62](https://github.com/PomeloProductions/polis-laravel/commit/a599b62a9bdb9fcee97adb43a083cceb5ded52a8))
* auto-resolve App\ overrides → fall back to package concretes in service providers ([cb15a96](https://github.com/PomeloProductions/polis-laravel/commit/cb15a9608ae1de86bd43abcd339457dce87b21a6))
* auto-resolve App\ overrides → fall back to package concretes in service providers ([d8e4216](https://github.com/PomeloProductions/polis-laravel/commit/d8e42162f7e4ee83a0598aa8dbc9adc310d5d701))
* HTMLPurifier-based email sanitizer + unique constraint on articles.key ([8206e92](https://github.com/PomeloProductions/polis-laravel/commit/8206e920cde857be075ee701a87d4113157b4388))
* HTMLPurifier-based email sanitizer + unique constraint on articles.key ([43da554](https://github.com/PomeloProductions/polis-laravel/commit/43da5540f33f648d3aaf51b9db915d6ec522150e))
* move ChargeRenewal command into polis-laravel + wire renewal_receipt template ([0daf240](https://github.com/PomeloProductions/polis-laravel/commit/0daf240a78f240daf5970eab2354676c2fffb11d))
* move ChargeRenewal command into polis-laravel + wire renewal_receipt template ([af29ffa](https://github.com/PomeloProductions/polis-laravel/commit/af29ffabb43003aba42fb000a9adb0a7b439c4b9))
* push notification template system + use it in ContactCreatedListener ([5c7cd15](https://github.com/PomeloProductions/polis-laravel/commit/5c7cd15678e9cf7b1e4033fb79183c6cf31aea80))
* push notification template system + use it in ContactCreatedListener ([8f41a36](https://github.com/PomeloProductions/polis-laravel/commit/8f41a3601d3b525df9df52befcc307a585de174d))
* runtime-editable email templates + move 4 listeners and 1 command ([538bcc2](https://github.com/PomeloProductions/polis-laravel/commit/538bcc2525e2f059375d0235561291633ea1c594))


### Bug Fixes

* **contracts:** tighten BaseRepositoryContract return types ([49305d1](https://github.com/PomeloProductions/polis-laravel/commit/49305d1bedcba53ba2576d4826ab028fcaaab6fe))
* correct FCM config key typo (comma -&gt; dot) ([283dae1](https://github.com/PomeloProductions/polis-laravel/commit/283dae1444cebe370cc3dda00170651b3d99df70))
* de-hardcode Slack username via config(polis.slack.username) ([75f0574](https://github.com/PomeloProductions/polis-laravel/commit/75f057494818761f32d76111567aa70403d47a0f))
* **deps:** declare runtime dependencies that src/ uses ([d11fa69](https://github.com/PomeloProductions/polis-laravel/commit/d11fa6965716fc545c16813d7ffc7623a10b0c54))


### Miscellaneous Chores

* 0.2.0 audit followups (items 6-18) ([bc6e9d9](https://github.com/PomeloProductions/polis-laravel/commit/bc6e9d9a87ae0bbb715aa49940f65e78cd650694))
* 0.2.0 pre-release blocker fixes ([c6a5e55](https://github.com/PomeloProductions/polis-laravel/commit/c6a5e55c17c98b12529367ac092f81ec6cd571b7))
* add .gitkeep to empty Statistics/ dirs ([4d0a769](https://github.com/PomeloProductions/polis-laravel/commit/4d0a7697380acf4c9013e4d17fffd803f436df98))
* delete TodoGenerationServiceTest (service does not exist in polis-laravel) ([ef1938d](https://github.com/PomeloProductions/polis-laravel/commit/ef1938d463107b60c09a4a50b7e6c6d23e017e91))
* fix typo (rpviders -&gt; providers) ([aefd933](https://github.com/PomeloProductions/polis-laravel/commit/aefd933806eb67854678a76cc554606f931e690c))
* raise coverage threshold to 38 (actual is ~40.7%) ([1765004](https://github.com/PomeloProductions/polis-laravel/commit/17650049aa75647b481501ae3eb38d0a8e2211bd))
* remove tracked AssetImportService.php.orig merge backup ([4ab66be](https://github.com/PomeloProductions/polis-laravel/commit/4ab66be6ddd555f6cc89f70d73e5ce1f0da02e8b))

## Changelog

This file is maintained by release-please.
