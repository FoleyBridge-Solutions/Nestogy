#!/bin/bash

# Generate new self-signed certificate for development
# This should be run in development environments only
# Production should use proper CA-signed certificates

CERT_DIR="$(dirname "$0")/.."
DAYS_VALID=365

# Generate private key and self-signed certificate
openssl req -x509 -newkey rsa:4096 -nodes \
  -out "$CERT_DIR/server.pem" \
  -keyout "$CERT_DIR/server.key" \
  -days $DAYS_VALID \
  -subj "/CN=localhost" 2>/dev/null

if [ $? -eq 0 ]; then
  echo "✓ Certificates generated successfully"
  echo "  - Private key: $CERT_DIR/server.key"
  echo "  - Certificate: $CERT_DIR/server.pem"
  echo ""
  echo "WARNING: These are self-signed development certificates."
  echo "For production, use certificates from a trusted certificate authority."
else
  echo "✗ Failed to generate certificates"
  exit 1
fi
