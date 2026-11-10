# Mobile Architecture

The mobile client uses feature-first Flutter structure with Riverpod state, GoRouter navigation, Dio repositories, and small domain-facing widgets. Laravel remains the authorization and financial source of truth.

The existing backend uses Sanctum’s stateful web session flow. The mobile client therefore uses a Dio cookie jar rather than inventing bearer-token behavior. A future mobile token contract must be designed and reviewed separately if session cookies are unsuitable for production mobile deployment.
