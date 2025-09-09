# Contributing to Nestogy MSP Platform

We welcome contributions from the community! Whether you're fixing bugs, adding features, improving documentation, or reporting issues, your help makes Nestogy better for everyone.

## Table of Contents

1. [Code of Conduct](#code-of-conduct)
2. [Getting Started](#getting-started)
3. [How to Contribute](#how-to-contribute)
4. [Development Process](#development-process)
5. [Coding Guidelines](#coding-guidelines)
6. [Testing Requirements](#testing-requirements)
7. [Documentation](#documentation)
8. [Community Guidelines](#community-guidelines)
9. [Recognition](#recognition)

## Code of Conduct

### Our Pledge

We pledge to make participation in our project a harassment-free experience for everyone, regardless of age, body size, disability, ethnicity, gender identity and expression, level of experience, nationality, personal appearance, race, religion, or sexual identity and orientation.

### Our Standards

Examples of behavior that contributes to a positive environment:

- Using welcoming and inclusive language
- Being respectful of differing viewpoints and experiences
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards other community members

Examples of unacceptable behavior:

- The use of sexualized language or imagery and unwelcome sexual attention or advances
- Trolling, insulting/derogatory comments, and personal or political attacks
- Public or private harassment
- Publishing others' private information without explicit permission
- Other conduct which could reasonably be considered inappropriate in a professional setting

### Enforcement

Project maintainers are responsible for clarifying standards and taking appropriate and fair corrective action in response to instances of unacceptable behavior.

## Getting Started

### Prerequisites

Before contributing, ensure you have:

- PHP 8.2+ development environment
- Laravel development experience
- Git knowledge
- Understanding of MSP (Managed Service Provider) workflows is helpful but not required

### Setting Up Development Environment

1. **Fork and Clone**
   ```bash
   # Fork the repository on GitHub
   # Clone your fork
   git clone https://github.com/YOUR_USERNAME/nestogy-erp.git
   cd nestogy-erp
   
   # Add upstream remote
   git remote add upstream https://github.com/foleybridge/nestogy-erp.git
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   # Configure your database settings in .env
   ```

4. **Setup Database**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Verify Installation**
   ```bash
   composer run test
   php artisan serve
   ```

## How to Contribute

### Reporting Bugs

Before creating bug reports, please:

1. **Search existing issues** to avoid duplicates
2. **Use the latest version** to ensure the bug still exists
3. **Include detailed information**:
   - Clear, descriptive title
   - Steps to reproduce
   - Expected vs actual behavior
   - Environment details (PHP version, OS, browser)
   - Screenshots or error messages if applicable

**Bug Report Template:**
```markdown
**Describe the Bug**
A clear description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '...'
3. Scroll down to '...'
4. See error

**Expected Behavior**
What you expected to happen.

**Screenshots**
If applicable, add screenshots.

**Environment:**
- OS: [e.g., Ubuntu 20.04]
- PHP Version: [e.g., 8.2.1]
- Laravel Version: [e.g., 11.0]
- Browser: [e.g., Chrome 120]

**Additional Context**
Any other context about the problem.
```

### Suggesting Features

For feature requests:

1. **Check existing discussions** and issues
2. **Explain the use case** and business value
3. **Describe the proposed solution**
4. **Consider alternatives** you've evaluated
5. **Be willing to implement** or help with implementation

**Feature Request Template:**
```markdown
**Feature Description**
A clear description of the feature you'd like to see.

**Use Case**
Describe the specific MSP workflow or problem this solves.

**Proposed Solution**
Detailed description of how you envision this working.

**Alternatives Considered**
Other approaches you've considered.

**Additional Context**
Screenshots, mockups, or examples from other tools.

**Implementation Willingness**
[ ] I'm willing to implement this feature
[ ] I'm willing to help with implementation
[ ] I'm suggesting this for someone else to implement
```

### Contributing Code

#### Types of Contributions

- **Bug fixes**: Small, focused fixes for specific issues
- **Features**: New functionality that adds value to MSPs
- **Improvements**: Performance, security, or UX enhancements
- **Documentation**: Updates to guides, comments, or examples
- **Tests**: Additional test coverage or test improvements

#### Before Starting

1. **Discuss significant changes** via GitHub issues first
2. **Review the roadmap** to ensure alignment
3. **Check for existing work** to avoid duplication
4. **Understand the architecture** (see [Architecture Documentation](./architecture/README.md))

## Development Process

### Git Workflow

We follow **GitFlow** with these branch conventions:

- `main`: Production-ready code
- `develop`: Integration branch for new features
- `feature/description`: New feature development
- `bugfix/description`: Bug fixes
- `hotfix/description`: Critical production fixes

### Step-by-Step Process

1. **Create Feature Branch**
   ```bash
   git checkout develop
   git pull upstream develop
   git checkout -b feature/your-feature-description
   ```

2. **Develop and Test**
   ```bash
   # Make your changes
   # Run tests frequently
   composer run test
   
   # Check code style
   ./vendor/bin/pint --test
   ```

3. **Commit Changes**
   ```bash
   # Use conventional commit format
   git add .
   git commit -m "feat: add client search functionality"
   ```

4. **Keep Branch Updated**
   ```bash
   git fetch upstream
   git rebase upstream/develop
   ```

5. **Push and Create PR**
   ```bash
   git push origin feature/your-feature-description
   # Create Pull Request via GitHub
   ```

### Commit Message Format

We use conventional commits:

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

**Examples:**
```
feat(clients): add bulk import functionality
fix(tickets): resolve status update bug
docs(api): update authentication examples
test(invoices): add validation tests
```

## Coding Guidelines

### PHP Standards

- Follow **PSR-12** coding standards
- Use **Laravel best practices**
- Write **self-documenting code** with clear variable names
- Add **comments** for complex business logic
- Use **type hints** and **return types**

### Code Quality

```php
// Good: Clear, typed, documented
/**
 * Create a new client with validation and notification
 */
public function createClient(array $data): Client
{
    $validatedData = $this->validateClientData($data);
    $client = Client::create($validatedData);
    
    event(new ClientCreated($client));
    
    return $client;
}

// Bad: Unclear, untyped, uncommented
public function create($data)
{
    return Client::create($data);
}
```

### Architecture Patterns

- **Domain-Driven Design**: Follow existing domain structure
- **Base Class Pattern**: Use BaseResourceController, BaseService, BaseRequest
- **Service Layer**: Business logic in domain-specific base services
- **Multi-Tenancy**: Mandatory BelongsToCompany trait on all models
- **Event-Driven**: Use Laravel events for decoupled communication

### Base Class Requirements (CRITICAL)

**All new controllers MUST extend BaseResourceController:**
```php
class YourController extends BaseResourceController
{
    use HasClientRelation; // Add domain-specific traits
    
    protected function initializeController(): void
    {
        $this->service = app(YourService::class);
        $this->resourceName = 'resource';
        $this->viewPath = 'domain.resources';
        $this->routePrefix = 'domain.resources';
    }
    
    protected function getModelClass(): string
    {
        return YourModel::class;
    }
}
```

**All new services MUST extend domain-specific base services:**
```php
class YourService extends ClientBaseService // or FinancialBaseService, AssetBaseService
{
    protected function initializeService(): void
    {
        $this->modelClass = YourModel::class;
        $this->defaultEagerLoad = ['client', 'user'];
        $this->searchableFields = ['name', 'description'];
    }
}
```

**All new models MUST use BelongsToCompany trait:**
```php
use App\Traits\BelongsToCompany;

class YourModel extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany; // REQUIRED
}
```

### Frontend Guidelines

- **Alpine.js**: For interactive components
- **Tailwind CSS**: For styling (use existing design system)
- **Progressive Enhancement**: Ensure functionality without JavaScript
- **Accessibility**: Follow WCAG guidelines

## Testing Requirements

### Test Coverage

- **All new features** must include tests
- **Bug fixes** must include regression tests
- **Aim for 80%+ coverage** on new code
- **Test both happy path and edge cases**

### Test Types

1. **Unit Tests**: Test individual methods/classes
   ```php
   public function test_client_service_creates_client_with_valid_data()
   {
       $user = User::factory()->create();
       $this->actingAs($user);
       
       $service = app(ClientService::class);
       $data = ['name' => 'Test Client', 'email' => 'test@example.com'];
       
       $client = $service->create($data);
       
       $this->assertInstanceOf(Client::class, $client);
       $this->assertEquals('Test Client', $client->name);
       $this->assertEquals($user->company_id, $client->company_id); // Test multi-tenancy
   }
   ```

2. **Feature Tests**: Test user workflows
   ```php
   public function test_authenticated_user_can_create_client()
   {
       $user = User::factory()->create();
       
       $response = $this->actingAs($user)
           ->post('/clients', [
               'name' => 'Test Client',
               'email' => 'test@example.com'
           ]);
       
       $response->assertRedirect()
           ->assertSessionHas('success');
       
       $this->assertDatabaseHas('clients', [
           'name' => 'Test Client',
           'company_id' => $user->company_id // Test multi-tenancy
       ]);
   }
   ```

3. **Browser Tests**: Test UI interactions (when needed)

### Running Tests

```bash
# Run all tests
composer run test

# Run specific test file
php artisan test tests/Feature/ClientTest.php

# Run with coverage
php artisan test --coverage

# Run tests in parallel
php artisan test --parallel
```

## Documentation

### Documentation Requirements

- **Update README** if installation/usage changes
- **Add code comments** for complex logic
- **Update API documentation** for API changes
- **Include usage examples** for new features
- **Update configuration docs** for new settings

### Documentation Style

- Use **clear, concise language**
- Include **practical examples**
- **Update existing docs** rather than duplicating
- **Link to related documentation**
- **Consider different user types** (developers, MSP staff, administrators)

## Community Guidelines

### Communication Channels

- **GitHub Issues**: Bug reports and feature discussions
- **Pull Requests**: Code review and collaboration
- **Discussions**: General questions and community support
- **Email**: security@foleybridge.com for security issues

### Being a Good Contributor

1. **Be respectful** and professional
2. **Help others** learn and contribute
3. **Share knowledge** through documentation and examples
4. **Review others' contributions** constructively
5. **Stay updated** with project direction and changes

### Code Review Process

When reviewing contributions:

- **Be constructive** and specific in feedback
- **Focus on the code**, not the person
- **Suggest improvements** with examples
- **Acknowledge good work**
- **Be responsive** to discussions

When your code is reviewed:

- **Be open to feedback**
- **Ask for clarification** if needed
- **Implement suggested changes** or discuss alternatives
- **Learn from the review** process

## Recognition

### Contributor Recognition

We recognize contributors through:

- **Contributors list** in README
- **Release notes** for significant contributions
- **Special thanks** in documentation
- **Maintainer status** for consistent, quality contributions

### Becoming a Maintainer

Active contributors may be invited to become maintainers. Maintainers have:

- **Commit access** to the repository
- **Review responsibilities** for pull requests
- **Decision-making input** on project direction
- **Mentoring opportunities** for new contributors

**Path to Maintainer:**
1. Consistent, quality contributions over time
2. Understanding of project goals and architecture
3. Positive community involvement
4. Willingness to review and mentor others

## Getting Help

### Resources

- **Development Guide**: [DEVELOPMENT.md](DEVELOPMENT.md)
- **Architecture Docs**: [architecture/README.md](architecture/README.md)
- **Testing Guide**: [TESTING.md](TESTING.md)
- **Laravel Documentation**: https://laravel.com/docs

### Support Channels

- **GitHub Discussions**: For general questions
- **GitHub Issues**: For specific problems
- **Code Reviews**: For learning and improvement
- **Community**: Connect with other contributors

### Questions?

Don't hesitate to ask questions! We're here to help:

1. **Search existing issues** first
2. **Create a new discussion** for general questions
3. **Tag maintainers** if you need specific help
4. **Be patient** - we're volunteers too!

---

**Thank you for contributing to Nestogy MSP Platform!** 

Your contributions help MSPs worldwide operate more efficiently and serve their clients better.

**Version**: 2.0.0 | **Last Updated**: August 2024 | **Platform**: Laravel 12 + PHP 8.2+ + Modern Base Class Architecture