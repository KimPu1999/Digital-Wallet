# Merchant analytics

Merchant reporting uses the existing `MerchantAccessService` and membership roles. Payment, refund, and settlement queries are filtered by the authorized merchant before execution. Cross-merchant identifiers are rejected.

Aggregates remain grouped by currency: gross successful volume, successful and failed counts, fees, refunds, and settlements. No cross-currency total is presented without an explicit valuation policy.
