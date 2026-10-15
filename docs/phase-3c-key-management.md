# Phase 3C evidence-key procedure

Phase 3C encrypts synthetic Google evidence locators with AES-256-GCM. Duplicate detection uses a separate HMAC key. Keys must be held in the deployment secret store and never committed, logged, copied into audit metadata, or shown in support screens.

## Initial setup

1. Generate independent random 32-byte values for the encryption key and fingerprint key.
2. Store them as deployment secrets in `EVIDENCE_ENCRYPTION_KEY_V1` and `EVIDENCE_FINGERPRINT_KEY`.
3. Set `EVIDENCE_CURRENT_KEY_VERSION=v1`.
4. Back up the secrets using the approved encrypted secret backup process and record custodians and recovery-test date outside the application database.
5. Restart workers and clear cached configuration. Confirm an encrypted round trip in the isolated sandbox without printing the locator or key.

Local and automated tests derive temporary keys from `APP_KEY` when these variables are absent. Production refuses that fallback.

## Rotation

1. Generate a new independent 32-byte key and store it in the next unused slot, such as `EVIDENCE_ENCRYPTION_KEY_V2`.
2. Deploy the new key while keeping the prior keys configured.
3. Set `EVIDENCE_CURRENT_KEY_VERSION=v2`, restart workers, and clear configuration cache.
4. Verify that new revisions record `v2` and an authorized check can still decrypt a known `v1` revision.
5. Retain old versions while any stored revision references them. Removal requires an approved retention action or a separately reviewed transactional re-encryption job.

The fingerprint key is not rotated casually because changing it affects duplicate comparison. Its rotation requires a separately reviewed fingerprint migration that never exposes decrypted identifiers.

## Recovery and compromise

- Restore only through named key custodians and the approved secret manager. Test recovery in an isolated environment.
- If an encryption key may be compromised, disable evidence opening, preserve audit evidence, rotate to a new version, and follow the approved incident process before any re-encryption.
- If a required historical key is lost, encrypted locators using it cannot be recovered by the application. Do not replace the version label with a different key.
- Never include keys, decrypted locators, Google file identifiers, or ciphertext in tickets, logs, database exports, screenshots, or notifications.
