# Mobile API Compatibility

| Capability | Existing endpoint | Status |
|---|---|---|
| Login/logout/session | `/api/v1/auth/login`, `/auth/logout`, `/auth/me` | Integrated |
| Wallet overview/list | `/api/v1/dashboard/wallet-overview`, `/wallets` | Integrated |
| Wallet balance/activity | `/wallets/{id}/balance`, `/ledger-activity` | Contract available; repository extension pending |
| FX | `/fx/pairs`, `/fx/quotes`, `/fx/quotes/{id}/convert` | Contract available; UI pending |
| Transfers | `/beneficiaries`, `/transfers/quotes`, `/transfers` | Contract available; UI pending |
| QR | `/qr/{token}`, `/qr/{token}/wallet-pay` | Sandbox contract available; camera UI pending |
| Notifications | `/notifications/*` | Contract available; UI pending |
| Support | `/support/tickets/*` | Contract available; UI pending |
| MFA/KYC/security | `/auth/mfa`, `/kyc`, `/security/*` | Contract available; mobile flows pending |

No unsupported response shape is treated as authoritative.
