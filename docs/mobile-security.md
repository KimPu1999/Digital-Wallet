# Mobile Security

- No API secrets are shipped in the app.
- Authentication uses the existing Sanctum session contract through a cookie-aware client.
- Credentials and session data are not logged.
- Sensitive local preferences use `flutter_secure_storage`.
- Biometric unlock uses `local_auth`; it does not override backend MFA, KYC, AML or risk controls.
- Financial POST retries require an idempotency contract and outcome reconciliation.
- QR payloads must be resolved and verified by Laravel before payment review.
- TLS certificate validation is not disabled.
- Android/iOS signing materials remain outside the repository.

Production release still requires platform threat modeling, secure crash reporting, privacy review and physical-device testing.
