# Flutter Web API Endpoints

All API routes are prefixed with `/api` and return JSON responses. Each response contains:

```json
{
  "success": true,
  "message": "Human readable summary",
  "data": { "..." }
}
```

Errors return `success: false`, an explanatory `message`, optional `errors` hashes for validation, and an appropriate HTTP status code.

## Authentication

| Method | Path | Description | Request Body |
| --- | --- | --- | --- |
| POST | `/api/auth/login` | Authenticate a customer account. | `username`, `password` (form encoded or JSON) |
| POST | `/api/auth/register` | Create a new customer account. | `firstname`, `lastname`, `email`, `phone`, `password` |
| POST | `/api/auth/logout` | Destroy the active session. | _None_ |

**Responses**
- `200 OK` with user and redirect data on successful login.
- `202 Accepted` for logins that trigger validation code e-mails.
- `201 Created` when registration succeeds.
- `4xx` for validation errors or invalid credentials.

## Products

| Method | Path | Description | Query |
| --- | --- | --- | --- |
| GET | `/api/products` | Paginated list of published products. | `limit` (default 50), `offset` |
| GET | `/api/products/{id-or-slug}` | Retrieve a single product by ID or slug. | _None_ |

**Response data**
```
{
  "items": [
    {
      "id": 1,
      "name": "Product name",
      "slug": "product-name",
      "price": 199.0,
      "image": "https://...",
      "available": 5
    }
  ],
  "limit": 50,
  "offset": 0
}
```

## Cart

| Method | Path | Description | Body |
| --- | --- | --- | --- |
| GET | `/api/cart` | Current cart snapshot. | _None_ |
| POST | `/api/cart/add` | Add one unit of a product to the cart. | `productId` |
| PATCH | `/api/cart` | Bulk update cart quantities. | `items` map of `productId` → `quantity` |
| DELETE | `/api/cart/{productId}` | Remove a product from the cart. | _None_ |
| POST | `/api/cart/coupon` | Apply or clear a coupon. | `coupon` (or `coupon_code`) |

The cart summary response includes `cart`, `cartCount`, and applied `discount`.

## Checkout

| Method | Path | Description | Body |
| --- | --- | --- | --- |
| POST | `/api/checkout` | Finalise checkout for the active cart. | Billing + payment fields |

**Required fields**: `firstname`, `lastname`, `street`, `city`, `phone`, `email`, `payment_method` (`card` or `cod`).
For card payments also supply `card_brand`, `card_last4`, and `card_token`. Optional `save_info` flag persists consent.

Successful requests respond with `201 Created`, the generated `order_id`, and a `redirect` URL for confirmation.

## Orders

| Method | Path | Description | Query |
| --- | --- | --- | --- |
| GET | `/api/orders` | Paginated list of the authenticated user's orders. | `limit` (default 5), `offset` |
| GET | `/api/orders/{id-or-slug}` | Retrieve a specific order. | _None_ |

Responses include normalized invoice metadata, ordered items (IDs, names, quantities, prices, totals) and any calculated totals block.

## Error Handling

- `401 Unauthorized` when the session is missing for protected endpoints (`/api/cart`, `/api/checkout`, `/api/orders`).
- `404 Not Found` for unknown endpoints or missing resources.
- `422 Unprocessable Entity` for validation failures.
- `409 Conflict` for duplicated resources (e.g., registering with an existing e-mail).
- `500 Internal Server Error` when persistence fails.

Send bodies as `application/json` or standard form data (`application/x-www-form-urlencoded`). All timestamps are ISO 8601 strings returned by the database.
