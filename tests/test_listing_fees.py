#!/usr/bin/env python3
"""Mirrors listing Toman fee sanitize/format/inject helpers."""

import re
import sys


PREFIX = "_listing_"

FEE_FIELDS = {
    "customs_fee": "هزینه گمرک",
    "shipping_fee": "هزینه حمل‌ونقل",
}


def sanitize_toman_fee(value):
    if isinstance(value, (list, tuple)):
        value = value[0] if value else ""
    if value is None or value is False:
        return ""
    value = str(value).strip()
    if value == "":
        return ""
    persian = "۰۱۲۳۴۵۶۷۸۹"
    arabic = "٠١٢٣٤٥٦٧٨٩"
    latin = "0123456789"
    trans = str.maketrans(persian + arabic, latin + latin)
    value = value.translate(trans)
    value = value.replace(",", "").replace("٬", "").replace("،", "").replace(" ", "")
    if "-" in value:
        return ""
    value = re.sub(r"[^\d.]", "", value)
    if value == "":
        return ""
    try:
        number = float(value)
    except ValueError:
        return ""
    if number < 0:
        return ""
    if number == int(number):
        return str(int(number))
    return str(number)


def is_listing_fee_field(field_id):
    return field_id in {PREFIX + suffix for suffix in FEE_FIELDS}


def format_toman_amount(amount, show_zero=False):
    if amount == "" or amount is None:
        if not show_zero:
            return ""
        amount = 0
    try:
        amount_f = float(amount)
    except (TypeError, ValueError):
        if not show_zero:
            return ""
        amount_f = 0
    if amount_f < 0:
        if not show_zero:
            return ""
        amount_f = 0
    if amount_f == 0 and not show_zero:
        return ""
    formatted = f"{int(round(amount_f)):,}"
    return f'<span class="price-text">{formatted}</span> <span class="suffix">تومان</span>'


def get_listing_fees_html(post_id, meta):
    post_id = abs(int(post_id or 0))
    if not post_id:
        return ""
    rows = []
    for suffix, label in FEE_FIELDS.items():
        formatted = format_toman_amount(meta.get(PREFIX + suffix, ""), True)
        rows.append(
            f'<span class="listing-price-extra listing-price-extra--{suffix}">'
            f'<span class="listing-price-extra-label">{label} : </span>'
            f'<span class="listing-price-extra-value">{formatted}</span>'
            "</span>"
        )
    return '<span class="listing-price-extras">' + "".join(rows) + "</span>"


def saved_fields_contain(fields, field_id):
    for field in fields or []:
        if not isinstance(field, dict):
            continue
        if field.get("type") == field_id or field.get("id") == field_id:
            return True
    return False


def inject_listing_fee_fields(fields, prefix=""):
    if prefix and prefix != PREFIX:
        return fields
    if not fields:
        return fields
    new_fields = [
        {"type": PREFIX + "customs_fee"},
        {"type": PREFIX + "shipping_fee"},
    ]
    missing = [cfg for cfg in new_fields if not saved_fields_contain(fields, cfg["type"])]
    if not missing:
        return fields
    result = []
    inserted = False
    price_id = PREFIX + "price"
    for field in fields:
        result.append(field)
        if inserted or not isinstance(field, dict):
            continue
        if field.get("type") == price_id or field.get("id") == price_id:
            result.extend(missing)
            inserted = True
    if not inserted:
        result.extend(missing)
    return result


def assert_same(expected, actual, message):
    if expected != actual:
        raise AssertionError(f"{message}: expected {expected!r}, got {actual!r}")
    print(f"PASS  {message}")


def assert_contains(needle, haystack, message):
    if needle not in haystack:
        raise AssertionError(f"{message}: missing {needle!r} in {haystack!r}")
    print(f"PASS  {message}")


def main():
    assert_same("500000", sanitize_toman_fee("500000"), "keeps a latin Toman amount")
    assert_same("500000", sanitize_toman_fee("۵۰۰۰۰۰"), "converts Persian digits")
    assert_same("900000", sanitize_toman_fee("900,000"), "strips thousand separators")
    assert_same("", sanitize_toman_fee(""), "empty stays empty")
    assert_same("", sanitize_toman_fee("-10"), "rejects negative amounts")
    assert_same(True, is_listing_fee_field("_listing_customs_fee"), "recognizes customs fee key")
    assert_same(True, is_listing_fee_field("_listing_shipping_fee"), "recognizes shipping fee key")
    assert_same(False, is_listing_fee_field("_listing_price"), "does not treat USD price as a fee")

    formatted = format_toman_amount(500000)
    assert_contains("500,000", formatted, "formats Toman with thousand separators")
    assert_contains("تومان", formatted, "appends تومان without converting from USD")
    assert_same("", format_toman_amount(0), "hides zero unless asked to show it")
    assert_same("", format_toman_amount(""), "hides empty unless asked to show zero")
    assert_contains("0", format_toman_amount("", True), "empty amount can render as 0")
    assert_contains("0", format_toman_amount(0, True), "zero amount can render as 0")

    fees_html = get_listing_fees_html(
        12,
        {"_listing_customs_fee": "500000", "_listing_shipping_fee": "900000"},
    )
    assert_contains("هزینه گمرک", fees_html, "renders customs label")
    assert_contains("هزینه حمل‌ونقل", fees_html, "renders shipping label")
    assert_contains("500,000", fees_html, "renders customs amount")
    assert_contains("900,000", fees_html, "renders shipping amount")
    assert_contains("listing-price-extras", fees_html, "wraps extras for stacking under the main price")

    partial = get_listing_fees_html(
        13,
        {"_listing_customs_fee": "", "_listing_shipping_fee": "900000"},
    )
    assert_contains("هزینه حمل‌ونقل", partial, "shows shipping when customs is empty")
    assert_contains("هزینه گمرک", partial, "keeps the empty customs row")
    assert_contains("listing-price-extra--customs_fee", partial, "renders the empty customs row wrapper")
    assert_contains(">0</span>", partial, "empty customs amount renders as 0")
    assert_same("", get_listing_fees_html(0, {}), "no HTML without a listing id")

    assert_same([], inject_listing_fee_fields([], PREFIX), "does not invent fields when Fields Manager data is empty")
    dealer = [{"type": "_dealer_name"}]
    assert_same(dealer, inject_listing_fee_fields(dealer, "_dealer_"), "leaves dealer fields alone")

    saved = [
        {"type": "_listing_year", "name": "Year"},
        {"type": "_listing_price", "name": "Price"},
        {"type": "_listing_price_prefix", "name": "Prefix"},
    ]
    injected = inject_listing_fee_fields(saved, PREFIX)
    assert_same("_listing_year", injected[0]["type"], "keeps fields before price")
    assert_same("_listing_price", injected[1]["type"], "keeps the price field")
    assert_same("_listing_customs_fee", injected[2]["type"], "inserts customs fee after price")
    assert_same("_listing_shipping_fee", injected[3]["type"], "inserts shipping fee after customs")
    assert_same("_listing_price_prefix", injected[4]["type"], "keeps fields that followed price")
    assert_same(5, len(inject_listing_fee_fields(injected, PREFIX)), "does not duplicate fee fields on a second pass")

    appended = inject_listing_fee_fields([{"type": "_listing_year"}], PREFIX)
    assert_same("_listing_customs_fee", appended[1]["type"], "appends fees when price is missing from saved data")
    assert_same("_listing_shipping_fee", appended[2]["type"], "appends shipping after customs when price is missing")

    print("\nAll listing fee tests passed")


if __name__ == "__main__":
    try:
        main()
    except AssertionError as exc:
        print(f"FAIL  {exc}")
        sys.exit(1)
