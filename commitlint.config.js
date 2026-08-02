// Overrides config-conventional's default type-enum (which includes `ci`
// and `revert`) with the qoomon/git-conventional-commits nomenclature:
// https://github.com/qoomon/git-conventional-commits — `ops` replaces `ci`
// (infra/deploy/CI-CD, broader than just pipelines), `revert` commits use
// git's own "Revert "..."" format instead of a type prefix.
module.exports = {
    extends: ['@commitlint/config-conventional'],
    rules: {
        'type-enum': [
            2,
            'always',
            ['feat', 'fix', 'refactor', 'perf', 'style', 'test', 'docs', 'build', 'ops', 'chore'],
        ],
    },
};
