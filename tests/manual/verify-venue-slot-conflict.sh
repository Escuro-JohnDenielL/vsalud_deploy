#!/usr/bin/env bash
#
# Manual verification: two parallel requests for the SAME venue + date + period,
# showing that only one of them creates a reservation. Both processes go through
# VenueOccupancyService::guard(), the same lock the admin conversion path uses.
#
# The probe refuses to run unless APP_ENV is "local" and the database host is
# localhost or 127.0.0.1. It inserts probe rows under a probe venue name and
# removes them again at the end.
#
# Usage:
#   bash tests/manual/verify-venue-slot-conflict.sh
#   bash tests/manual/verify-venue-slot-conflict.sh "Probe Venue X" 2099-12-31 AM

set -u

VENUE="${1:-Probe Venue X}"
DATE="${2:-2099-12-31}"
PERIOD="${3:-AM}"
PROBE="tests/manual/venue-slot-probe.php"

cd "$(dirname "$0")/../.." || exit 1

echo "Checking environment..."
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
