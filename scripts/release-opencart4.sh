#!/usr/bin/env bash
set -euo pipefail

repo=razorpay/razorpay-opencart
tag=${1:-}

if [[ $# -ne 1 || ! $tag =~ ^opencart4-[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
  echo "Usage: $0 opencart4-x.y.z" >&2
  exit 1
fi

for command in git gh python3 tar zip unzip mktemp; do
  command -v "$command" >/dev/null || {
    echo "Required command is missing: $command" >&2
    exit 1
  }
done

cd "$(dirname "$0")/.."
git rev-parse --show-toplevel >/dev/null

release_ok=$(gh release view "$tag" --repo "$repo" --json isDraft,targetCommitish --jq '.isDraft == false and .targetCommitish == "master"')
if [[ $release_ok != true ]]; then
  echo "Release must be published and target master: $tag" >&2
  exit 1
fi

git fetch --no-tags "https://github.com/$repo.git" \
  refs/heads/master:refs/remotes/origin/master \
  "refs/tags/$tag:refs/tags/$tag"

if ! git merge-base --is-ancestor "$tag^{commit}" refs/remotes/origin/master; then
  echo "Release tag is not on master: $tag" >&2
  exit 1
fi

temp_dir=$(mktemp -d)
trap 'rm -rf "$temp_dir"' EXIT
mkdir "$temp_dir/source"
git archive "$tag" admin catalog system install.json | tar -x -C "$temp_dir/source"

cd "$temp_dir/source"
package_version=$(python3 -c 'import json; print(json.load(open("install.json"))["version"])')
if [[ $tag != "opencart4-$package_version" ]]; then
  echo "Release tag and install.json version differ" >&2
  exit 1
fi

package="$temp_dir/razorpay.ocmod.zip"
zip -q -r "$package" admin catalog system install.json -x '*/.*'
unzip -tq "$package"
entries=$(unzip -Z1 "$package")
if grep -Eq '(^|/)(\.[^/]+|__MACOSX)(/|$)|^README\.md$' <<< "$entries"; then
  echo "Package contains unexpected files" >&2
  exit 1
fi

gh release upload "$tag" "$package" --repo "$repo"
echo "Uploaded razorpay.ocmod.zip to $repo release $tag"
