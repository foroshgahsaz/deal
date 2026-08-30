#!/usr/bin/env python3
"""Mirrors WP_CarDealer_Navasan BrsApi extract_rate_from_payload and convert_amount."""

import json
import sys


DEFAULT_ITEM = "USD"


def flatten_brs_items(data):
    if not isinstance(data, dict) and not isinstance(data, list):
        return []
    if isinstance(data, dict) and isinstance(data.get("data"), (dict, list)):
        data = data["data"]
    rows = []
    if isinstance(data, dict):
        for section in ("gold", "currency", "cryptocurrency"):
            if isinstance(data.get(section), list):
                rows.extend([row for row in data[section] if isinstance(row, dict)])
        if rows:
            return rows
        return []
    if isinstance(data, list) and data and isinstance(data[0], dict):
        return data
    return []


def price_to_toman(price, unit):
    try:
        price_f = float(price)
    except (TypeError, ValueError):
        return 0.0
    if price_f <= 0:
        return 0.0
    unit = (unit or "").strip()
    if unit == "ریال" or unit.lower() in ("rial", "irr"):
        return price_f / 10
    return price_f


def extract_rate_from_payload(data, item=DEFAULT_ITEM):
    result = {
        "rate": 0.0,
        "item": item,
        "name": "",
        "date": "",
        "timestamp": 0,
        "change": "",
        "unit": "تومان",
    }
    rows = flatten_brs_items(data)
    match = None
    for row in rows:
        if str(row.get("symbol", "")).upper() == str(item).upper():
            match = row
            break
    if not match or match.get("price") is None:
        return result
    try:
        rate = price_to_toman(match["price"], match.get("unit", "تومان"))
    except (TypeError, ValueError):
        return result
    if rate <= 0:
        return result
    date = str(match.get("date", "") or "")
    time = str(match.get("time", "") or "")
    result["rate"] = float(rate)
    result["item"] = str(match.get("symbol"))
    result["name"] = str(match.get("name", "") or "")
    result["date"] = (date + (" " + time if time else "")).strip()
    result["timestamp"] = int(match.get("time_unix") or 0)
    result["change"] = match.get("change_value", "")
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


def resolve_cached_rate(transient, fallback):
    try:
        transient_f = float(transient)
        if transient_f > 0:
            return transient_f
    except (TypeError, ValueError):
        pass
    try:
        fallback_f = float(fallback)
        if fallback_f > 0:
            return fallback_f
    except (TypeError, ValueError):
        pass
    return 0.0


def assert_same(expected, actual, message):
    if expected != actual:
        raise AssertionError(f"{message}: expected {expected!r}, got {actual!r}")
    print(f"PASS  {message}")


def main():
    payload = {
        "gold": [],
        "currency": [
            {
                "date": "1405/06/04",
                "time": "18:14",
                "time_unix": 1787755476,
                "symbol": "USDT_IRT",
                "name": "دلار تتر",
                "price": 198726,
                "change_value": -358,
                "unit": "تومان",
            },
            {
                "date": "1405/06/04",
                "time": "18:14",
                "time_unix": 1787755476,
                "symbol": "USD",
                "name_en": "US Dollar",
                "name": "دلار",
                "price": 200500,
                "change_value": 0,
                "unit": "تومان",
            },
        ],
    }
    parsed = extract_rate_from_payload(payload, "USD")
    assert_same(200500.0, parsed["rate"], "extracts USD.price from BrsApi currency list")
    assert_same("USD", parsed["item"], "keeps USD symbol")
    assert_same("دلار", parsed["name"], "extracts Persian name")
    assert_same("1405/06/04 18:14", parsed["date"], "joins BrsApi date and time")
    assert_same(1787755476, parsed["timestamp"], "extracts time_unix")
    assert_same(198726.0, extract_rate_from_payload(payload, "USDT_IRT")["rate"], "extracts USDT_IRT from the same payload")
    assert_same(200500.0, extract_rate_from_payload(payload["currency"], "USD")["rate"], "accepts a flat list of currency rows")
    assert_same(0.0, extract_rate_from_payload({"foo": "bar"}, "USD")["rate"], "returns 0 for invalid payload")
    assert_same(0.0, extract_rate_from_payload(None, "USD")["rate"], "returns 0 for non-array payload")
    assert_same(200500.0, price_to_toman(200500, "تومان"), "keeps Toman prices as-is")
    assert_same(85900.0, price_to_toman(859000, "ریال"), "converts Rial prices to Toman")
    assert_same(5012500000.0, convert_amount(25000, 200500), "converts 25000 USD at 200500 Toman")
    assert_same(0.0, convert_amount("abc", 200500), "non-numeric USD becomes 0")
    assert_same(25000.0, convert_amount(25000, 0), "rate 0 leaves the USD amount unchanged")
    assert_same(200500.0, resolve_cached_rate(200500, 0), "prefers transient rate")
    assert_same(198726.0, resolve_cached_rate(False, 198726), "falls back to last stored rate")
    assert_same(0.0, resolve_cached_rate(False, 0), "returns 0 when nothing is cached")

    sample = json.loads(
        '{"currency":[{"symbol":"USD","price":200500,"unit":"تومان","date":"1405/06/04","time":"18:14","time_unix":1787755476,"name":"دلار"}]}'
    )
    assert_same(200500.0, extract_rate_from_payload(sample)["rate"], "parses JSON from BrsApi gold/currency docs")

    print("\nAll BrsApi conversion tests passed")


if __name__ == "__main__":
    try:
        main()
    except AssertionError as exc:
        print(f"FAIL  {exc}")
        sys.exit(1)
