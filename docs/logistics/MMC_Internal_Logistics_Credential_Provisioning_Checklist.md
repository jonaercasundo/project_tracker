# MMC Internal Logistics Credential Provisioning Checklist

**Classification:** Internal only — do not distribute to the Logistics partner  
**Purpose:** Provision, rotate, and revoke managed Logistics Channel webhook credentials safely.

Never place live secret values in this document. The partner receives only the credential key ID and secret through an approved secure channel.

## Provisioning

- [ ] Obtain the partner name and stable integration ID. The managed UI accepts letters, digits, dot, dash, and underscore, up to 64 characters.
- [ ] Confirm the production endpoint, enabled event types, project IDs, school scope, and whether all schools in selected projects are approved.
- [ ] Select only explicitly authorized projects; do not grant project or school scope by inference.
- [ ] Check each expected DR against MMC delivery records. Ensure each DR resolves to one project/school receipt scope within the integration's scope.
- [ ] For legitimate DR reuse, obtain business approval and create the exact integration + DR → project + school mapping before partner traffic is enabled.
- [ ] Create the managed integration through the Logistics Channel UI as an authorized MMC user. The application generates a separate credential key ID and HMAC-SHA256 secret.
- [ ] Transfer key ID and secret to the named partner recipient through the approved secret-sharing channel. Never send credentials in ordinary email, chat, tickets, source code, screenshots, shell history, or this checklist.
- [ ] Record integration ID, key ID, owner, issue date, approved project/school scope, event types, transfer acknowledgement, and planned rotation date in the internal credential inventory. Do not record the secret value.
- [ ] Confirm secret access is limited to named operators and that the partner has stored the secret in an approved secret manager.
- [ ] Run signed UAT requests with the issued credential and verify that the secret is absent from application logs and admin listings.

## Rotation

- [ ] Agree on cutover time with partner and issue a new credential.
- [ ] Coordinate the old-key overlap. The managed flow supports 1–30 days; the UI defaults to 7 days.
- [ ] Partner switches to the new key ID and secret; verify valid request signatures and successful UAT events.
- [ ] Revoke or allow the old credential to expire after confirmed cutover.
- [ ] Update internal inventory with rotation and expiry metadata, without recording any secret.

## Compromise or emergency revocation

- [ ] Disable the integration or revoke the compromised credential promptly using the approved incident process.
- [ ] Notify the incident owner, MMC operations, security contact, and partner through approved channels.
- [ ] Issue a replacement credential and verify partner cutover.
- [ ] Review integration audit history and webhook event activity for suspicious use.
- [ ] Document incident and recovery actions without copying secrets or raw signed payloads into the incident record.

## Confirm before production provisioning

- [ ] Production credential provisioning roles and approvers.
- [ ] Approved secure secret-transfer mechanism and recipient verification process.
- [ ] Internal credential inventory owner and retention policy.
- [ ] Rotation notice window and overlap selection.
- [ ] Emergency revocation authority, on-call owner, and partner escalation contacts.
- [ ] Whether all active credentials must be revoked during a suspected integration compromise.
