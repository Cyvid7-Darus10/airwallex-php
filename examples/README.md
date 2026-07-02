# Examples

Runnable walkthroughs for every major flow. Each script reads credentials
from the environment and targets the **demo** sandbox:

```bash
export AIRWALLEX_CLIENT_ID=your_client_id
export AIRWALLEX_API_KEY=your_api_key
php examples/payout_quickstart.php
```

| Script | Flow |
| --- | --- |
| [payout_quickstart.php](payout_quickstart.php) | Balances → indicative rate → payout → sandbox lifecycle |
| [fx_quote_and_convert.php](fx_quote_and_convert.php) | Indicative rate, lockable quote, executed conversion |
| [accept_payment.php](accept_payment.php) | Payment intent → confirm → refund |
| [issuing_quickstart.php](issuing_quickstart.php) | Cardholder → virtual card → limits and transactions |
| [webhook_handler.php](webhook_handler.php) | Verifying and routing incoming webhooks (drop-in endpoint) |
| [pagination_and_errors.php](pagination_and_errors.php) | Auto-pagination, manual paging, typed error handling, retries |
| [custom_http_client.php](custom_http_client.php) | Injecting your own PSR-18 client (proxy, observability) |

Create demo credentials in the Airwallex web app under **Developer → API keys**
(demo environment toggle in the top bar).
