#!/usr/bin/env bash
# =============================================================================
# BizCore ERP — MySQL Automated Backup Script
#
# Usage:
#   ./backup.sh                        # uses environment variables
#   MYSQL_DATABASE=mydb ./backup.sh    # override database name
#
# Environment variables (all have sensible defaults):
#   MYSQL_HOST        MySQL hostname            (default: 127.0.0.1)
#   MYSQL_PORT        MySQL port                (default: 3306)
#   MYSQL_USER        MySQL username            (default: root)
#   MYSQL_PASSWORD    MySQL password            (default: "")
#   MYSQL_DATABASE    Database to back up       (default: bizcore_erp)
#   BACKUP_DIR        Where to store dumps      (default: /var/backups/mysql)
#   RETENTION_DAYS    Days to keep old backups  (default: 7)
#   LOG_FILE          Log output file           (default: /var/log/mysql-backup.log)
# =============================================================================

set -euo pipefail

# ----------------------------------------------------------------
# Configuration (override via environment)
# ----------------------------------------------------------------
MYSQL_HOST="${MYSQL_HOST:-127.0.0.1}"
MYSQL_PORT="${MYSQL_PORT:-3306}"
MYSQL_USER="${MYSQL_USER:-root}"
MYSQL_PASSWORD="${MYSQL_PASSWORD:-}"
MYSQL_DATABASE="${MYSQL_DATABASE:-bizcore_erp}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/mysql}"
RETENTION_DAYS="${RETENTION_DAYS:-7}"
LOG_FILE="${LOG_FILE:-/var/log/mysql-backup.log}"

# ----------------------------------------------------------------
# Internal variables
# ----------------------------------------------------------------
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
DUMP_FILENAME="${MYSQL_DATABASE}_${TIMESTAMP}.sql"
DUMP_FILE="${BACKUP_DIR}/${DUMP_FILENAME}"
COMPRESSED_FILE="${DUMP_FILE}.gz"
SCRIPT_NAME="$(basename "$0")"

# ----------------------------------------------------------------
# Logging helper
# ----------------------------------------------------------------
log() {
    local level="$1"
    shift
    local message="$*"
    local timestamp
    timestamp="$(date '+%Y-%m-%d %H:%M:%S')"
    local line="[$timestamp] [$level] [$SCRIPT_NAME] $message"
    echo "$line"
    echo "$line" >> "$LOG_FILE" 2>/dev/null || true
}

log_info()  { log "INFO " "$@"; }
log_warn()  { log "WARN " "$@"; }
log_error() { log "ERROR" "$@"; }

# ----------------------------------------------------------------
# Pre-flight checks
# ----------------------------------------------------------------
preflight_checks() {
    log_info "Running pre-flight checks..."

    # Ensure backup directory exists
    if [ ! -d "$BACKUP_DIR" ]; then
        log_info "Backup directory '$BACKUP_DIR' does not exist — creating it."
        mkdir -p "$BACKUP_DIR"
    fi

    # Ensure log directory exists
    local log_dir
    log_dir="$(dirname "$LOG_FILE")"
    if [ ! -d "$log_dir" ]; then
        mkdir -p "$log_dir"
    fi

    # Check required tools
    for cmd in mysqldump gzip; do
        if ! command -v "$cmd" &>/dev/null; then
            log_error "Required command '$cmd' not found in PATH."
            exit 1
        fi
    done

    # Check MySQL connectivity
    if ! mysqladmin \
            --host="$MYSQL_HOST" \
            --port="$MYSQL_PORT" \
            --user="$MYSQL_USER" \
            --password="$MYSQL_PASSWORD" \
            ping --silent 2>/dev/null; then
        log_error "Cannot connect to MySQL at ${MYSQL_HOST}:${MYSQL_PORT}."
        exit 1
    fi

    log_info "Pre-flight checks passed."
}

# ----------------------------------------------------------------
# Create database dump
# ----------------------------------------------------------------
create_dump() {
    log_info "Starting dump of database '${MYSQL_DATABASE}'..."

    mysqldump \
        --host="$MYSQL_HOST" \
        --port="$MYSQL_PORT" \
        --user="$MYSQL_USER" \
        --password="$MYSQL_PASSWORD" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-database \
        --databases "$MYSQL_DATABASE" \
        > "$DUMP_FILE"

    local dump_size
    dump_size="$(du -sh "$DUMP_FILE" | cut -f1)"
    log_info "Dump created: ${DUMP_FILE} (${dump_size})"
}

# ----------------------------------------------------------------
# Compress dump with gzip
# ----------------------------------------------------------------
compress_dump() {
    log_info "Compressing dump file..."

    gzip -9 "$DUMP_FILE"

    local compressed_size
    compressed_size="$(du -sh "$COMPRESSED_FILE" | cut -f1)"
    log_info "Compressed file: ${COMPRESSED_FILE} (${compressed_size})"
}

# ----------------------------------------------------------------
# Verify compressed archive
# ----------------------------------------------------------------
verify_backup() {
    log_info "Verifying backup integrity..."

    if gzip -t "$COMPRESSED_FILE" 2>/dev/null; then
        log_info "Backup integrity check passed."
    else
        log_error "Backup integrity check FAILED for '${COMPRESSED_FILE}'."
        exit 1
    fi
}

# ----------------------------------------------------------------
# Remove backups older than RETENTION_DAYS
# ----------------------------------------------------------------
cleanup_old_backups() {
    log_info "Cleaning up backups older than ${RETENTION_DAYS} day(s) in '${BACKUP_DIR}'..."

    local deleted_count=0

    # Find and remove old .sql.gz files matching this database
    while IFS= read -r -d '' old_file; do
        log_info "Removing old backup: ${old_file}"
        rm -f "$old_file"
        deleted_count=$((deleted_count + 1))
    done < <(find "$BACKUP_DIR" \
                -maxdepth 1 \
                -name "${MYSQL_DATABASE}_*.sql.gz" \
                -mtime +"$RETENTION_DAYS" \
                -print0)

    if [ "$deleted_count" -eq 0 ]; then
        log_info "No old backups to remove."
    else
        log_info "Removed ${deleted_count} old backup(s)."
    fi
}

# ----------------------------------------------------------------
# Print backup summary
# ----------------------------------------------------------------
print_summary() {
    local backup_count
    backup_count="$(find "$BACKUP_DIR" -maxdepth 1 -name "${MYSQL_DATABASE}_*.sql.gz" | wc -l | tr -d ' ')"

    log_info "------------------------------------------------------"
    log_info "Backup summary:"
    log_info "  Database  : ${MYSQL_DATABASE}"
    log_info "  Host      : ${MYSQL_HOST}:${MYSQL_PORT}"
    log_info "  File      : ${COMPRESSED_FILE}"
    log_info "  Retention : ${RETENTION_DAYS} days"
    log_info "  Stored    : ${backup_count} backup(s) in ${BACKUP_DIR}"
    log_info "  Status    : SUCCESS"
    log_info "------------------------------------------------------"
}

# ----------------------------------------------------------------
# Trap: clean up uncompressed dump on unexpected exit
# ----------------------------------------------------------------
cleanup_on_error() {
    if [ -f "$DUMP_FILE" ]; then
        log_warn "Removing partial dump file after error: ${DUMP_FILE}"
        rm -f "$DUMP_FILE"
    fi
    log_error "Backup FAILED."
    exit 1
}
trap cleanup_on_error ERR

# ----------------------------------------------------------------
# Main entry point
# ----------------------------------------------------------------
main() {
    log_info "======================================================"
    log_info "BizCore ERP MySQL Backup — $(date '+%Y-%m-%d %H:%M:%S')"
    log_info "======================================================"

    preflight_checks
    create_dump
    compress_dump
    verify_backup
    cleanup_old_backups
    print_summary
}

main "$@"
