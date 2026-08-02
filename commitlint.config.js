// Extends config-conventional's default type-enum (which already has
// `feat fix refactor perf style test docs build ci chore revert`) with one
// addition: `security`, for a fix that specifically closes a
// vulnerability — kept distinct from a plain `fix` so it's easy to spot in
// `git log` and in changelogs. See .gitmessage for the full type list with
// emojis and descriptions, and .claude/skills/semantic-commit-messages/.
// scope-empty: config-conventional leaves scope optional by default —
// this repo requires it on every commit ("<type>(<scope>): ...", never
// bare "<type>: ..."), so the area touched is always visible in `git log`
// without opening the diff.
module.exports = {
    extends: ['@commitlint/config-conventional'],
    rules: {
        'type-enum': [
            2,
            'always',
            [
                'feat',
                'fix',
                'refactor',
                'perf',
                'style',
                'test',
                'docs',
                'build',
                'ci',
                'chore',
                'revert',
                'security',
            ],
        ],
        'scope-empty': [2, 'never'],
    },
};
