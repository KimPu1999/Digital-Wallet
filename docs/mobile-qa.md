# Phase 16 Mobile QA

## Audit status

| Area | Status | Evidence |
|---|---|---|
| Flutter project structure | Partial | `mobile/` foundation exists; native projects require `flutter create .` |
| Authentication/session | Partial | Sanctum cookie-aware login/client exists; device validation pending |
| Wallet reads | Partial | Overview/list repository and screens exist; balance/activity detail pending |
| Transfers, FX, QR | Missing | Backend contracts are documented; mobile flows are not implemented |
| Notifications/support/KYC | Missing | Backend contracts exist; mobile screens are pending |
| Themes/localization | Partial | EN/MM and light/dark foundation exists; full screen audit pending |
| Automated tests | Untested | Flutter SDK unavailable locally |
| Android/iOS builds | Untested | Flutter and Android SDK unavailable; only xcodebuild is present |

## Required test layers

Unit, widget, integration, API-contract, security regression, accessibility, performance and physical-device testing must run against isolated sandbox services. Missing evidence remains blocked in the mobile release gates.

