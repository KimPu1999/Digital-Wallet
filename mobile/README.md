# Digital Wallet Mobile

Flutter customer client foundation for the sandbox Laravel API.

Implemented foundation:

- Riverpod + GoRouter application structure.
- Cookie-aware Dio client for the existing Sanctum stateful session API.
- Login, logout, session restoration and backend-authoritative wallet overview.
- Home, wallets, activity and profile shells.
- Light/dark theme and English/Myanmar locale switching.
- Secure storage abstraction for biometric preference.
- `local_auth` biometric service without bypassing backend MFA.
- Sandbox-only labels and no local balance calculations.

Transfer, FX, QR, KYC, notifications, support, native permissions and release signing require Flutter tooling and device validation before being marked complete. No mobile bearer-token endpoint was invented; the existing API authenticates through Sanctum session cookies.

## Setup

```bash
flutter doctor
flutter pub get
dart run build_runner build --delete-conflicting-outputs
flutter analyze
flutter test
flutter build apk --debug --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
```

For iOS, use macOS/Xcode and run `flutter build ios --no-codesign`. Configure bundle identifiers and privacy descriptions without committing signing materials.

`API_BASE_URL` is passed at build/run time. Android emulators use `10.0.2.2`; iOS simulators use `127.0.0.1`. Never embed provider credentials or backend secrets.

The mobile client does not persist authentication tokens or calculate authoritative balances. Session cookies are held by the cookie-aware client. Biometric unlock protects the app session and does not replace server MFA for sensitive actions. All financial operations remain sandbox-only.
