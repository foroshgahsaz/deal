#!/usr/bin/env python3
"""Mirrors WP_CarDealer_Navasan::extract_rate_from_payload and convert_amount."""

import json
import sys


DEFAULT_ITEM = "usd_sell"


def extract_rate_from_payload(data, item=DEFAULT_ITEM):
    result = {
        "rate": 0.0,
        "item": item,
        "date": "",
        "timestamp": 0,
        "change": "",
    }
    if not isinstance(data, dict):
        return result

    row = None
    if item in data and isinstance(data[item], dict):
        row = data[item]
    elif "value" in data:
        row = data
    else:
        for maybe_row in data.values():
            if isinstance(maybe_row, dict) and "value" in maybe_row:
                row = maybe_row
                break

    if not isinstance(row, dict) or "value" not in row:
        return result

    try:
        rate = float(row["value"])
    except (TypeError, ValueError):
        return result

    result["rate"] = rate
    result["date"] = str(row.get("date", ""))
    result["timestamp"] = int(row.get("timestamp", 0) or 0)
    result["change"] = row.get("change", "")
    return result


def convert_amount(usd, rate):
    try:
        usd_f = float(usd)
    except (TypeError, ValueError):
        return 0.0
    try:
        rate_f = float(rate)
    except (TypeError, ValueError):
        return usd_f
    if rate_f <= 0:
        return usd_f
    return float(round(usd_f * rate_f))


def assert_same(expected, actual, message):
    if expected != actual:
        raise AssertionError(f"{message}: expected {expected!r}, got {actual!r}")
    print(f"PASS  {message}")


def main():
    payload = {
        "usd_sell": {
            "value": "11100",
            "change": -25,
            "timestamp": 1568212950,
            "date": "1398-06-20 19:12:30",
        }
    }
    parsed = extract_rate_from_payload(payload, "usd_sell")
    assert_same(11100.0, parsed["rate"], "extracts usd_sell.value from latest payload")
    assert_same("1398-06-20 19:12:30", parsed["date"], "extracts Navasan date")
    assert_same(1568212950, parsed["timestamp"], "extracts timestamp")

    parsed_flat = extract_rate_from_payload(
        {
            "value": "95000",
            "change": 10,
            "timestamp": 1700000000,
            "date": "1402-01-01 12:00:00",
        },
        "usd_sell",
    )
    assert_same(95000.0, parsed_flat["rate"], "extracts value from a flat item payload")

    assert_same(0.0, extract_rate_from_payload({"foo": "bar"}, "usd_sell")["rate"], "returns 0 for invalid payload")
    assert_same(0.0, extract_rate_from_payload(None, "usd_sell")["rate"], "returns 0 for non-array payload")

    assert_same(277500000.0, convert_amount(25000, 11100), "converts 25000 USD at 11100 Toman")
    assert_same(0.0, convert_amount("abc", 11100), "non-numeric USD becomes 0")
    assert_same(25000.0, convert_amount(25000, 0), "rate 0 leaves the USD amount unchanged")
    assert_same(25000.0, convert_amount(25000, -1), "negative rate leaves the USD amount unchanged")
    assert_same(277500001.0, convert_amount(25000.00009, 11100), "rounds converted Toman to nearest integer")
    assert_same(11050.0, extract_rate_from_payload({"usd_buy": {"value": "11050"}}, "usd_buy")["rate"], "extracts a non-default item key")

    sample = json.loads(
        '{"usd_sell":{"value":"11100","change":-25,"timestamp":1568212950,"date":"1398-06-20 19:12:30"}}'
    )
    assert_same(11100.0, extract_rate_from_payload(sample)["rate"], "parses JSON from Navasan docs")

    print("\nAll Navasan conversion tests passed")


if __name__ == "__main__":
    try:
        main()
    except AssertionError as exc:
        print(f"FAIL  {exc}")
        sys.exit(1)
