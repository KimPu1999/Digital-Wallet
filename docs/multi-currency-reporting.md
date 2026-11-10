# Multi-currency reporting

Supported sandbox currencies are USD, EUR, GBP, SGD, THB, and MMK. Reports retain ISO codes and integer minor-unit amounts. Currency totals are grouped by code and never directly summed across currencies.

Future base-currency views must create a `report_valuations` record containing reporting currency, source currency, decimal rate, source/version, observation time, valuation date, and rounding policy.
