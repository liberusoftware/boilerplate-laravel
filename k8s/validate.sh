#!/bin/bash

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
K8S_DIR="${SCRIPT_DIR}"

log_info()    { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }
log_error()   { echo -e "${RED}[ERROR]${NC} $1"; }

check_prerequisites() {
    log_info "Checking prerequisites..."
    local missing_tools=()
    if ! command -v kubectl &> /dev/null; then
        missing_tools+=("kubectl")
    fi
    if ! command -v kustomize &> /dev/null; then
        log_warning "kustomize not found, will use kubectl kustomize instead"
    fi
    if [ ${#missing_tools[@]} -gt 0 ]; then
        log_error "Missing required tools: ${missing_tools[*]}"
        return 1
    fi
    log_success "All prerequisites met"
}

validate_yaml_syntax() {
    log_info "Validating YAML syntax..."
    local errors=0
    for file in "$K8S_DIR"/base/*.yaml; do
        if [ -f "$file" ]; then
            if kubectl apply --dry-run=client -f "$file" &> /dev/null; then
                log_success "✓ $(basename "$file")"
            else
                log_error "✗ $(basename "$file") - Invalid YAML"
                errors=$((errors + 1))
            fi
        fi
    done
    if [ $errors -eq 0 ]; then
        log_success "All YAML files are valid"
        return 0
    else
        log_error "$errors YAML file(s) failed validation"
        return 1
    fi
}

validate_kustomize() {
    log_info "Validating Kustomize builds..."
    local environments=("development" "production")
    local errors=0
    for env in "${environments[@]}"; do
        local overlay_dir="$K8S_DIR/overlays/$env"
        if [ -d "$overlay_dir" ]; then
            log_info "Validating $env environment..."
            if kubectl kustomize "$overlay_dir" > /dev/null 2>&1; then
                log_success "✓ $env overlay builds successfully"
            else
                log_error "✗ $env overlay failed to build"
                errors=$((errors + 1))
            fi
        else
            log_warning "Overlay directory not found: $overlay_dir"
        fi
    done
    if [ $errors -eq 0 ]; then
        log_success "All Kustomize overlays are valid"
        return 0
    else
        log_error "$errors overlay(s) failed validation"
        return 1
    fi
}

check_cluster_connectivity() {
    log_info "Checking cluster connectivity..."
    if kubectl cluster-info &> /dev/null; then
        log_success "Connected to Kubernetes cluster"
        kubectl cluster-info | head -n 1
        return 0
    else
        log_error "Cannot connect to Kubernetes cluster"
        return 1
    fi
}

check_namespace() {
    local namespace="${1:-boilerplate-laravel}"
    log_info "Checking namespace: $namespace..."
    if kubectl get namespace "$namespace" &> /dev/null; then
        log_success "Namespace '$namespace' exists"
        return 0
    else
        log_warning "Namespace '$namespace' does not exist"
        read -p "Create namespace? (y/N) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            kubectl create namespace "$namespace" && log_success "Namespace created"
        fi
    fi
}

validate_deployment() {
    local namespace="${1:-boilerplate-laravel}"
    log_info "Checking deployment status in namespace: $namespace..."
    if ! kubectl get namespace "$namespace" &> /dev/null; then
        log_error "Namespace '$namespace' does not exist"
        return 1
    fi
    if kubectl get deployment -n "$namespace" boilerplate-laravel &> /dev/null; then
        local replicas ready available
        replicas=$(kubectl get deployment -n "$namespace" boilerplate-laravel -o jsonpath='{.status.replicas}')
        ready=$(kubectl get deployment -n "$namespace" boilerplate-laravel -o jsonpath='{.status.readyReplicas}')
        available=$(kubectl get deployment -n "$namespace" boilerplate-laravel -o jsonpath='{.status.availableReplicas}')
        log_info "Deployment: replicas=$replicas ready=${ready:-0} available=${available:-0}"
        if [ "${ready:-0}" -eq "$replicas" ] && [ "${available:-0}" -eq "$replicas" ]; then
            log_success "Deployment is healthy"
        else
            log_warning "Deployment is not fully ready"
            kubectl get pods -n "$namespace" -l app=boilerplate-laravel
            kubectl get events -n "$namespace" --sort-by='.lastTimestamp' | tail -10
        fi
    else
        log_warning "Deployment 'boilerplate-laravel' not found in namespace '$namespace'"
    fi
}

main() {
    echo ""
    echo "╔═══════════════════════════════════════════════════════════╗"
    echo "║  Boilerplate Laravel - K8s Validation                   ║"
    echo "╚═══════════════════════════════════════════════════════════╝"
    echo ""

    local namespace="${1:-boilerplate-laravel}"
    local skip_cluster="${SKIP_CLUSTER_CHECKS:-false}"

    check_prerequisites || exit 1

    echo ""
    validate_yaml_syntax || { log_error "YAML validation failed"; exit 1; }

    echo ""
    validate_kustomize || { log_error "Kustomize validation failed"; exit 1; }

    if [ "$skip_cluster" = "false" ]; then
        echo ""
        check_cluster_connectivity || {
            log_warning "Cluster not reachable — skipping live checks (set SKIP_CLUSTER_CHECKS=true to suppress)"
            exit 0
        }
        echo ""
        check_namespace "$namespace"
        echo ""
        validate_deployment "$namespace"
    else
        log_info "Skipping cluster checks (SKIP_CLUSTER_CHECKS=true)"
    fi

    echo ""
    log_success "Validation complete"
}

main "$@"
