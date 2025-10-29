-- Populate new payment method metadata for existing invoices
-- Heuristic:
--  - If status is 'A' (active/paid) set method to 'card'
--  - Otherwise set to 'cod' (cash on delivery / pending)
--  - Ensure lpa_inv_save_info is not null

UPDATE lpa_invoices
SET lpa_inv_payment_method = 'card'
WHERE (lpa_inv_payment_method IS NULL OR lpa_inv_payment_method = '')
  AND lpa_inv_status = 'A';

UPDATE lpa_invoices
SET lpa_inv_payment_method = 'cod'
WHERE (lpa_inv_payment_method IS NULL OR lpa_inv_payment_method = '')
  AND lpa_inv_status <> 'A';

UPDATE lpa_invoices
SET lpa_inv_save_info = COALESCE(lpa_inv_save_info, 0);

-- Optional: clear card meta where method is COD (safety)
UPDATE lpa_invoices
SET lpa_inv_card_brand = NULL,
    lpa_inv_card_last4 = NULL
WHERE lpa_inv_payment_method = 'cod';

