#!/usr/bin/env python3
"""重新產生台灣(TW)IP 範圍資料檔。

用途:當台灣 IP 配置有變動時,重新下載 RIR 委派資料並產生
app/Core/GeoData/tw_ipv4_ranges.php 與 tw_ipv6_ranges.php。

資料來源(公開、離線後即不需連線):
  https://raw.githubusercontent.com/ipverse/rir-ip/master/country/tw/ipv4-aggregated.txt
  https://raw.githubusercontent.com/ipverse/rir-ip/master/country/tw/ipv6-aggregated.txt

用法:
  1. 下載上述兩個檔到本目錄,分別命名 tw-ipv4-aggregated.txt / tw-ipv6-aggregated.txt
  2. 於專案根目錄執行:  python3 tools/geoip/build_tw_ranges.py
"""
import ipaddress
import datetime
import os

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.abspath(os.path.join(HERE, "..", ".."))
OUT = os.path.join(ROOT, "app", "Core", "GeoData")


def load(fn):
    nets = []
    with open(fn, encoding="utf-8") as fh:
        for line in fh:
            line = line.strip()
            if not line or line.startswith("#"):
                continue
            nets.append(ipaddress.ip_network(line, strict=False))
    return nets


def merge(nets):
    ranges = sorted((int(n.network_address), int(n.broadcast_address)) for n in nets)
    merged = []
    for s, e in ranges:
        if merged and s <= merged[-1][1] + 1:
            merged[-1][1] = max(merged[-1][1], e)
        else:
            merged.append([s, e])
    return merged


def main():
    v4 = merge(load(os.path.join(HERE, "tw-ipv4-aggregated.txt")))
    v6 = merge(load(os.path.join(HERE, "tw-ipv6-aggregated.txt")))
    stamp = datetime.date.today().isoformat()

    with open(os.path.join(OUT, "tw_ipv4_ranges.php"), "w", encoding="utf-8") as f:
        f.write("<?php\n\n")
        f.write(f"// 台灣(TW)IP 位址範圍 — 由 APNIC/RIR 委派資料彙整(ipverse/rir-ip),產生日期 {stamp}。\n")
        f.write("// 請勿手動編輯;更新請以 tools/geoip/build_tw_ranges.py 重新產生。IPv4 為 [起,迄] 之 uint32 區間,已排序合併。\n\n")
        f.write("return [\n")
        for s, e in v4:
            f.write(f"    [{s}, {e}],\n")
        f.write("];\n")

    with open(os.path.join(OUT, "tw_ipv6_ranges.php"), "w", encoding="utf-8") as f:
        f.write("<?php\n\n")
        f.write(f"// 台灣(TW)IPv6 位址範圍 — 由 APNIC/RIR 委派資料彙整(ipverse/rir-ip),產生日期 {stamp}。\n")
        f.write("// 請勿手動編輯。每筆為 [起,迄] 之 32 位十六進位字串(inet_pton 之 bin2hex),已排序合併。\n\n")
        f.write("return [\n")
        for s, e in v6:
            f.write(f"    ['{format(s, '032x')}', '{format(e, '032x')}'],\n")
        f.write("];\n")

    print(f"IPv4 ranges: {len(v4)}  IPv6 ranges: {len(v6)}  (stamp {stamp})")


if __name__ == "__main__":
    main()
