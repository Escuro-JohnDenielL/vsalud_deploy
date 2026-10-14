#!/usr/bin/env bash
#
# Manual verification: two parallel requests for the SAME venue + date + period,
# showing that only one of them creates a reservation. Both processes go through
# VenueOccupancyService::guard(), the same lock the admin conversion path uses.
#
# Requirements:
#   * APP_ENV must be "local" and the database host must be localhost or
#     127.0.0.1. The probe refuses to run otherwise.
#   * Run it with CACHE_STORE=database, against a local COPY of the database that
#     already has the cache and cache_locks tables. That is the only way the run
#     exercises the cache_locks path: with the default "file" store the lock is a
#     file lock and cache_locks is never touched. The store in use is printed,
#     and a warning is printed when it is not "database".
#
# It inserts probe rows under a probe venue name and removes them again at the
# end.
#
# Usage:
#   CACHE_STORE=database bash tests/manual/verify-venue-slot-conflict.sh
#   CACHE_STORE=database bash tests/manual/verify-venue-slot-conflict.sh "Probe Venue X" 2099-12-31 AM

set -u

VENUE="${1:-Probe Venue X}"
DATE="${2:-2099-12-31}"
PERIOD="${3:-AM}"
PROBE="tests/manual/venue-slot-probe.php"

cd "$(dirname "$0")/../.." || exit 1

echo "Checking environment (set CACHE_STORE=database to exercise cache_locks)..."
if ! php "${PROBE}" --check; then
    echo "Aborting: this script only runs locally."
    exit 1
fi

OUT_A="$(mktemp)"
OUT_B="$(mktemp)"

echo
echo "Slot: venue='${VENUE}' date='${DATE}' period='${PERIOD}'"
echo "Starting two parallel guarded creates..."
echo

php "${PROBE}" "${VENUE}" "${DATE}" "${PERIOD}" > "${OUT_A}" 2>&1 &
PID_A=$!
php "${PROBE}" "${VENUE}" "${DATE}" "${PERIOD}" > "${OUT_B}" 2>&1 &
PID_B=$!

wait "${PID_A}"
wait "${PID_B}"

echo "--- request A ---"
cat "${OUT_A}"
echo "--- request B ---"
cat "${OUT_B}"
echo

A_CREATED=$(grep -c 'outcome=created' "${OUT_A}" || true)
B_CREATED=$(grep -c 'outcome=created' "${OUT_B}" || true)
A_CONFLICT=$(grep -c 'outcome=conflict' "${OUT_A}" || true)
B_CONFLICT=$(grep -c 'outcome=conflict' "${OUT_B}" || true)
A_BUSY=$(grep -c 'outcome=busy' "${OUT_A}" || true)
B_BUSY=$(grep -c 'outcome=busy' "${OUT_B}" || true)

CREATED=$((A_CREATED + B_CREATED))
CONFLICT=$((A_CONFLICT + B_CONFLICT))
BUSY=$((A_BUSY + B_BUSY))

echo "created=${CREATED} conflict=${CONFLICT} busy=${BUSY}"

if [ "${CREATED}" = "1" ] && [ "${CONFLICT}" = "1" ]; then
    echo "PASS: exactly one request created the reservation."
elif [ "${CREATED}" = "1" ] && [ "${BUSY}" = "1" ]; then
    echo "PASS: one created, the other failed closed while the lock was held."
else
    echo "FAIL: expected exactly one success (created=1, and conflict=1 or busy=1)."
fi

echo
echo "Cleaning up probe rows..."
php "${PROBE}" --cleanup "${VENUE}" "${DATE}"

rm -f "${OUT_A}" "${OUT_B}"
