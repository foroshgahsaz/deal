# Sync `deal` customizations → `cardealer`

The full plugin tree (all custom changes from PR #1–#18) lives on branch **`cursor/cardealer-export-deb0`** in this repo.

## One-time setup (owner)

1. Create a **fine-grained PAT** with **Contents: Read and write** on `foroshgahsaz/cardealer`.
2. In **foroshgahsaz/deal** → Settings → Secrets → Actions → add **`CARDEALER_SYNC_TOKEN`**.
3. Actions → **Sync cardealer from deal export** → **Run workflow**.

Alternatively, grant **Cursor GitHub App** (or `cursor[bot]`) **Write** access to `foroshgahsaz/cardealer` so Cloud Agents can push directly.

## Manual push (no Actions)

```bash
git clone https://github.com/foroshgahsaz/cardealer.git
cd cardealer
git fetch https://github.com/foroshgahsaz/deal.git cursor/cardealer-export-deb0
git checkout main
git merge --ff-only FETCH_HEAD   # or: git reset --hard FETCH_HEAD
git push origin main
```
