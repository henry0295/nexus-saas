#!/bin/bash

TOKEN="4|EJs7pI1v0jnRKYyA0vBZJmKFi24MhSfjf1gNFBcSd009ad00"
BASE_URL="https://192.168.101.99"

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║          TESTING NEW API ENDPOINTS                             ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Test 1: Create Email Template
echo "1️⃣  CREATE EMAIL TEMPLATE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
curl -s -k -X POST "$BASE_URL/api/email-templates" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name":"Welcome","subject":"Welcome!","body":"Hello {{name}}"}'  \
  -w "\n\nHTTP Status: %{http_code}\n\n"

# Test 2: List Email Templates
echo ""
echo "2️⃣  LIST EMAIL TEMPLATES"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
curl -s -k -X GET "$BASE_URL/api/email-templates" \
  -H "Authorization: Bearer $TOKEN" \
  -w "\n\nHTTP Status: %{http_code}\n\n"

# Test 3: Create SMS Template
echo ""
echo "3️⃣  CREATE SMS TEMPLATE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
curl -s -k -X POST "$BASE_URL/api/sms-templates" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name":"Code","message":"Your code: {{code}}"}' \
  -w "\n\nHTTP Status: %{http_code}\n\n"

# Test 4: Create Email Sender
echo ""
echo "4️⃣  CREATE EMAIL SENDER"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
curl -s -k -X POST "$BASE_URL/api/email-senders" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name":"Support","email":"support@example.com"}' \
  -w "\n\nHTTP Status: %{http_code}\n\n"

# Test 5: Create Email Domain
echo ""
echo "5️⃣  CREATE EMAIL DOMAIN"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
curl -s -k -X POST "$BASE_URL/api/email-domains" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"subdomain":"mail","domain":"example.com"}' \
  -w "\n\nHTTP Status: %{http_code}\n\n"

# Test 6: Get Dashboard Stats
echo ""
echo "6️⃣  GET DASHBOARD STATS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
curl -s -k -X GET "$BASE_URL/api/dashboard/stats" \
  -H "Authorization: Bearer $TOKEN" \
  -w "\n\nHTTP Status: %{http_code}\n\n"

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║          ALL TESTS COMPLETED                                   ║"
echo "╚════════════════════════════════════════════════════════════════╝"
