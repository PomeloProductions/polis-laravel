# Changelog

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
