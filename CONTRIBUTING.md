# Contributing

Contributions are **welcome** and will be fully **credited**.

We accept contributions via Pull Requests on [Github](https://github.com/bmwsly/mondial-relay-api).

## Pull Requests

- **[PSR-12 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-12-extended-coding-style-guide.md)** - Check the code style with ``$ composer check-style`` and fix it with ``$ composer fix-style``.

- **Add tests!** - Your patch won't be accepted if it doesn't have tests.

- **Document any change in behaviour** - Make sure the `README.md` and any other relevant documentation are kept up-to-date.

- **Consider our release cycle** - We try to follow [SemVer v2.0.0](http://semver.org/). Randomly breaking public APIs is not an option.

- **Create feature branches** - Don't ask us to pull from your master branch.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please [squash them](http://www.git-scm.com/book/en/v2/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages) before submitting.

## Running Tests

```bash
$ composer test
```

## Code Style

We use PHP CS Fixer to maintain code style. You can check and fix the code style with:

```bash
$ composer check-style
$ composer fix-style
```

## Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Copy `.env.example` to `.env` and configure your Mondial Relay credentials
4. Run tests: `composer test`

## Adding New Features

When adding new features:

1. Add appropriate tests
2. Update documentation
3. Follow existing code patterns
4. Ensure backward compatibility
5. Add changelog entry

## Reporting Issues

When reporting issues, please include:

1. PHP version
2. Laravel version
3. Package version
4. Steps to reproduce
5. Expected vs actual behavior
6. Any relevant error messages

**Happy coding**!
