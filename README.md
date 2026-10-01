# omnibus/db-schenker

DB Schenker for [glitchr/omnibus](https://github.com/glitchr-studio/omnibus): tracking through
eSchenker's public tracking (no credentials), and land-transport bookings through the Schenker
Open API when an API key is configured. Prices come from configuration (`rates`): Schenker quotes
by contract.

```yaml
omnibus:
    gateways:
        db-schenker:
            factory: db-schenker
            options:
                api_key: '%env(SCHENKER_API_KEY)%'        # optional: bookings
                account_number: '%env(SCHENKER_ACCOUNT)%'
                rates:
                    - { service: SYSTEM, label: 'DB Schenker System', bands: { 30000: 2900 } }
```

The booking action is built on the Open API's published shape and is **unverified**: it needs an
account's key (from the [Schenker partner portal](https://www.dbschenker.com/global/digital-solutions/api))
to be run against the service. Tracking is live.

License: LGPL-3.0-or-later.
