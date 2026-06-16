# Phase 7: Admin daily workflow

## Morning routine

1. **WooCommerce → Orders**
2. Click status filter: **Pending payment**
3. Process oldest orders first

## Per order checklist

| Step | Action |
|------|--------|
| 1 | Open order — note **Order #** and **UTR** |
| 2 | Open bank / UPI app — search UTR or amount + date |
| 3 | Verify amount **exactly matches** order total |
| 4 | Verify payer name roughly matches customer (optional) |
| 5 | If valid → status **Processing** → Update |
| 6 | If invalid → Add order note → call/WhatsApp customer → **Cancelled** if no response |

## Packing and shipping

1. Print packing slip: order → **Print**
2. After dispatch → status **Completed**
3. Add tracking number in order notes or use shipping plugin

## Bulk actions

- Select multiple verified orders → Bulk actions → **Change status to processing**

## Common issues

| Issue | Fix |
|-------|-----|
| Customer paid wrong amount | Contact for balance or refund difference |
| No UTR entered | Call customer; do not process until UTR provided |
| Duplicate UTR | Check if same UTR used on two orders — fraud risk |
| Customer paid without order # in remarks | Match by amount + time + name |

## Reports

**WooCommerce → Analytics → Orders** — daily revenue after moving to Processing/Completed.
