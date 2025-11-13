# Branch Protection Rules

This document describes the branch protection rules configured for this repository.

## Protected Branches

### `master` / `main`

The main branch is protected with the following rules:

#### Pull Request Requirements

- ✅ **Require pull request reviews before merging**
  - Minimum number of approvals: **1**
  - Require review from code owners: **Yes**
  - Dismiss stale pull request approvals when new commits are pushed: **Yes**

#### Status Checks

The following status checks must pass before merging:

1. **Laravel Pint** - Code style checking
2. **PHPStan** - Static analysis
3. **PHPUnit Tests** - Test suite execution
4. **Composer Security Audit** - Dependency vulnerability scanning

- ✅ **Require branches to be up to date before merging**: Yes
- ✅ **Require conversation resolution before merging**: Yes

#### Additional Protection

- ✅ **Require linear history**: Yes (no merge commits)
- ✅ **Require signed commits**: Recommended (can be enabled)
- ❌ **Allow force pushes**: No
- ❌ **Allow deletions**: No
- ✅ **Do not allow bypassing the above settings**: Yes (applies to admins)

## Setting Up Branch Protection

### Using GitHub CLI

1. Install GitHub CLI: https://cli.github.com/
2. Authenticate: `gh auth login`
3. Run the protection script:

```bash
chmod +x .github/scripts/protect-branch.sh
./github/scripts/protect-branch.sh master
```

### Using GitHub Web Interface

1. Go to repository **Settings** → **Branches**
2. Click **Add rule** or edit existing rule for `master`/`main`
3. Configure the following:

#### Branch name pattern
```
master
```
(or `main` if that's your default branch)

#### Protect matching branches

**Pull request requirements:**
- ☑ Require a pull request before merging
  - ☑ Require approvals: `1`
  - ☑ Dismiss stale pull request approvals when new commits are pushed
  - ☑ Require review from Code Owners

**Status checks:**
- ☑ Require status checks to pass before merging
  - ☑ Require branches to be up to date before merging
  - Status checks to require:
    - `Laravel Pint`
    - `PHPStan`
    - `PHPUnit Tests`
    - `Composer Security Audit`

**Additional rules:**
- ☑ Require conversation resolution before merging
- ☑ Require linear history
- ☑ Do not allow bypassing the above settings
  - ☑ Restrict who can bypass: Administrators only

**Restrictions:**
- ☐ Restrict pushes that create matching branches (optional)

## Status Checks

All status checks are defined in `.github/workflows/ci.yml`:

- **Laravel Pint**: Runs `vendor/bin/pint --test`
- **PHPStan**: Runs `vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=1G`
- **PHPUnit Tests**: Runs `php artisan test`
- **Composer Security Audit**: Runs `composer audit`

## Code Owners

Code owners are defined in `.github/CODEOWNERS`. Pull requests that modify files owned by specific teams will require approval from those code owners.

## Bypassing Protection (Emergency Only)

In emergency situations, administrators can bypass branch protection by:

1. Using GitHub's "Update branch" button (if enabled)
2. Temporarily disabling protection rules (not recommended)
3. Using force push with admin privileges (if allowed)

**Note**: All bypass actions are logged and should be documented.

## Troubleshooting

### Status checks not appearing

1. Ensure the workflow file (`.github/workflows/ci.yml`) is in the repository
2. Check that the job names match the status check names exactly
3. Verify the workflow has run at least once on a pull request

### Cannot merge PR

Common reasons:
- Missing required approvals
- Status checks are failing
- Branch is not up to date with base branch
- Unresolved conversations in the PR

### Force push needed

If you need to force push (not recommended):
1. Temporarily disable branch protection
2. Perform the force push
3. Re-enable branch protection immediately
4. Document the reason for the force push

## Related Documentation

- [GitHub Branch Protection Documentation](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches/about-protected-branches)
- [CODEOWNERS Documentation](https://docs.github.com/en/repositories/managing-your-repositorys-settings-and-features/customizing-your-repository/about-code-owners)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)

