#!/usr/bin/env python3
import concurrent.futures
import os
import random
import ssl
import statistics
import sys
import threading
import time
import urllib.error
import urllib.parse
import urllib.request

BASE=os.environ.get("STAGING_BASE_URL","").strip().rstrip("/")
CONCURRENCY=max(1,min(50,int(os.environ.get("LOAD_CONCURRENCY","10"))))
DURATION=max(10,min(180,int(os.environ.get("LOAD_DURATION","45"))))
TIMEOUT=max(2,min(20,int(os.environ.get("LOAD_TIMEOUT","10"))))

if not BASE:
    raise SystemExit("STAGING_BASE_URL is required")
parsed=urllib.parse.urlparse(BASE)
host=(parsed.hostname or "").lower()
if parsed.scheme!="https":
    raise SystemExit("Load target must use HTTPS")
if host in {"akhikhan.ru","www.akhikhan.ru"} or host.endswith(".akhikhan.ru") and "staging" not in host and "stage" not in host:
    raise SystemExit("Refusing to load-test production akhikhan.ru. Use a dedicated staging hostname.")

PATHS=[
    "/",
    "/news.php",
    "/privacy.php",
    "/documents.php",
    "/gallery.php",
    "/search.php?q=%D1%81%D0%BF%D0%BE%D1%80%D1%82",
]
ctx=ssl.create_default_context()
stop_at=time.monotonic()+DURATION
lock=threading.Lock()
latencies=[]
statuses={}
errors=[]

def hit(worker_id:int):
    local=[]
    while time.monotonic()<stop_at:
        path=random.choice(PATHS)
        url=BASE+path
        started=time.monotonic()
        status=0
        try:
            req=urllib.request.Request(url,headers={
                "User-Agent":"AKHIKHAN-Staging-Load/1.0",
                "Accept":"text/html,application/xhtml+xml",
                "Cache-Control":"no-cache",
            },method="GET")
            with urllib.request.urlopen(req,timeout=TIMEOUT,context=ctx) as resp:
                status=int(resp.status)
                resp.read(32768)
        except urllib.error.HTTPError as exc:
            status=int(exc.code)
        except Exception as exc:
            with lock:
                errors.append(type(exc).__name__)
        elapsed=(time.monotonic()-started)*1000
        local.append((elapsed,status))
        time.sleep(0.05)
    with lock:
        for elapsed,status in local:
            latencies.append(elapsed)
            statuses[status]=statuses.get(status,0)+1

with concurrent.futures.ThreadPoolExecutor(max_workers=CONCURRENCY) as pool:
    list(pool.map(hit,range(CONCURRENCY)))

total=sum(statuses.values())+len(errors)
ok=sum(count for code,count in statuses.items() if 200<=code<400)
success=(ok/total*100) if total else 0.0
p50=statistics.median(latencies) if latencies else 0.0
ordered=sorted(latencies)
p95=ordered[min(len(ordered)-1,max(0,int(len(ordered)*0.95)-1))] if ordered else 0.0
rps=total/DURATION if DURATION else 0.0

print(f"Target: {BASE}")
print(f"Duration: {DURATION}s | concurrency: {CONCURRENCY} | requests: {total} | avg RPS: {rps:.2f}")
print(f"HTTP: {dict(sorted(statuses.items()))} | transport errors: {len(errors)}")
print(f"Success: {success:.2f}% | p50: {p50:.0f} ms | p95: {p95:.0f} ms")

if total<CONCURRENCY:
    raise SystemExit("Too few completed requests")
if success<99.0:
    raise SystemExit("Failure threshold exceeded: success rate < 99%")
if p95>3000:
    raise SystemExit("Latency threshold exceeded: p95 > 3000 ms")
