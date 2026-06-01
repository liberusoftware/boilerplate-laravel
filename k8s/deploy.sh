#!/usr/bin/env bash
# Kubernetes Deployment Script for Liberu Boilerplate Laravel

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

NAMESPACE="${NAMESPACE:-boilerplate-laravel}"
ENVIRONMENT="${ENVIRONMENT:-production}"
DOMAIN="${DOMAIN:-boilerplate.example.com}"
APP_KEY="${APP_KEY:-}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-}"

info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }

echo -e "${GREEN}=== Liberu Boilerplate Kubernetes Deployment ===${NC}"

command -v kubectl >/dev/null 2>&1 || { error "kubectl not installed"; exit 1; }

[ -z "$APP_KEY" ]          && { error "APP_KEY is required (php artisan key:generate --show)"; exit 1; }
[ -z "$DB_PASSWORD" ]      && { error "DB_PASSWORD is required"; exit 1; }
[ -z "$DB_ROOT_PASSWORD" ] && { error "DB_ROOT_PASSWORD is required"; exit 1; }

info "Creating namespace: $NAMESPACE"
kubectl create namespace "$NAMESPACE" --dry-run=client -o yaml | kubectl apply -f -

info "Updating secrets..."
kubectl create secret generic boilerplate-secrets \
    --from-literal=APP_KEY="$APP_KEY" \
    --from-literal=DB_USERNAME="liberu" \
    --from-literal=DB_PASSWORD="$DB_PASSWORD" \
    --from-literal=DB_ROOT_PASSWORD="$DB_ROOT_PASSWORD" \
    --from-literal=REDIS_PASSWORD="" \
    --namespace="$NAMESPACE" \
    --dry-run=client -o yaml | kubectl apply -f -

info "Deploying to $ENVIRONMENT..."
kubectl apply -k "k8s/overlays/$ENVIRONMENT"

info "Waiting for deployment..."
kubectl wait --for=condition=available --timeout=300s \
    deployment/boilerplate-laravel -n "$NAMESPACE" || warn "Timeout waiting for deployment"

info "Deployment complete!"
echo ""
echo "  Status:  kubectl get pods -n $NAMESPACE"
echo "  Logs:    kubectl logs -n $NAMESPACE -l app=boilerplate-laravel"
echo "  URL:     https://$DOMAIN"
