# Blackbird CSP Manager

[![Latest Stable Version](https://img.shields.io/packagist/v/blackbird/module-csp-manager.svg?style=flat-square)](https://packagist.org/packages/blackbird/module-csp-manager)
[![License: MIT](https://img.shields.io/github/license/blackbird-agency/magento-2-csp-manager.svg?style=flat-square)](./LICENSE.txt)

This module allows managing Content Security Policy (CSP) rules directly from the Magento command line (CLI).
The rules are stored in the `app/etc/env.php` file, which allows for environment-specific management without going through the database or the backoffice.

**Important:** The rules managed by this module are **added to** (not replacing) the existing Magento CSP rules defined in `csp_whitelist.xml` files.

Key functionality includes:
- Manage additional (additive to `csp_whitelist.xml`) CSP values via CLI.
- Automatic injection of rules into the native Magento collector (`Magento\Csp\Model\CompositePolicyCollector`).
- Management of automatic CSP header splitting (`Content-Security-Policy` and `Content-Security-Policy-Report-Only`):
    - Splitting into multiple headers if the length exceeds a configurable limit (default 8192 bytes).
    - Configuration possible via the Backoffice (Services > CSP Manager).

### Security

This module is designed to be **safe from SQL injections**. All configuration is handled through the Magento CLI and stored directly in the `app/etc/env.php` file. Since no database storage is used for the CSP rules themselves, it eliminates the risk of SQL-based attacks for CSP management.

Additionally, this approach provides several security benefits:
- **Protects against stolen Back Office accounts**: Even if an administrator account is compromised, the attacker cannot modify CSP rules via the Magento Admin Panel, as the configuration is stored in a file that is typically read-only for the web server and only manageable via CLI.
- **Prevents automatic allowing of injected scripts**: Malicious scripts that might gain access to the database cannot dynamically white-list themselves by injecting new CSP rules into the configuration, ensuring that the security policies remain under strict developer/sysadmin control.

The source code is available at the [GitHub repository](https://github.com/blackbird-agency/magento-2-csp-manager).

---

## Setup

### Get the Package

#### **Zip Package:**

Unzip the package into `app/code/Blackbird/CSPManager`, from the root of your Magento instance.

#### **Composer Package:**

```shell
composer require blackbird/module-csp-manager
```

### Install the Module

Go to your Magento root directory, then run the following Magento commands:

**If you are in production mode, do not forget to recompile and redeploy the static resources, or to use the `--keep-generated` option.**

```shell
bin/magento module:enable Blackbird_CSPManager
bin/magento setup:upgrade
bin/magento cache:flush
```

---

## Features

### Usage via CLI

#### Add value(s) to a directive

Adds one or more values to an existing or new directive. Note that these values are added additionally to any values already defined in Magento's `csp_whitelist.xml` files.

```bash
bin/magento csp:rule:add [directive] "[value1]" "[value2]" ...
```

Example:
```bash
bin/magento csp:rule:add img-src "mysite.com" "anothersite.com"
```

If the directive does not exist, it is created in env.php. If it exists, the new values are added to existing values (no duplicates).

#### Overwrite/Set a complete directive

Sets the values for a directive in `env.php`. Note that these values are **still additive** to those defined in `csp_whitelist.xml` files; this command only overwrites other values managed by this module in `env.php`.

```bash
bin/magento csp:rule:set [directive] "[value]"
```

Example:
```bash
bin/magento csp:rule:set img-src "mysite.com cdn.mysite.com"
```

This command replaces the current value of the directive with the provided one.

#### Unset a complete directive

```bash
bin/magento csp:rule:unset [directive]
```

Example:
```bash
bin/magento csp:rule:unset img-src
```

This command is the inverse of `csp:rule:set` to completely remove a directive.

#### List rules

```bash
bin/magento csp:rule:list
```

#### Remove value(s) or a directive

```bash
bin/magento csp:rule:remove [directive] [value1] [value2] ...
```

Example to remove specific value(s):
```bash
bin/magento csp:rule:remove img-src "mysite.com" "anothersite.com"
```

Example to remove a complete directive:
```bash
bin/magento csp:rule:remove img-src
```

Note: The `add`, `set`, `remove`, and `unset` commands validate that the directive passed as an argument is valid according to the CSP specification (list from MDN).

**Important:** After making any changes to CSP rules, you should clean the `full_page` cache to reflect the changes on the frontend:
```bash
bin/magento cache:clean full_page
```

### Technical Configuration

The rules are stored in `app/etc/env.php` under the `csp` key:

```php
<?php
return [
    'csp' => [
        'img-src' => 'mysite.com cdn.mysite.dam',
        'script-src' => 'scripts.cdn.com'
    ],
    // ... rest of config
];
```

The module uses a custom collector `Blackbird\CSPManager\Model\Collector\EnvPolicyCollector` injected into `Magento\Csp\Model\CompositePolicyCollector`.

A plugin on `Magento\Csp\Model\CspRenderer` ensures that CSP headers are split if necessary to avoid header size errors on some servers (default 8KB limit).

When a header is split, the module uses the principle that multiple CSP headers are combined by the browser as an **intersection** (a resource must be allowed by all headers).

To ensure that the split headers do not become more restrictive than the original single header, the module ensures that:
- The `default-src` directive is **repeated in every split header part** with its original values (staying "closed"). This ensures that any directive not explicitly defined in a part still falls back to the original `default-src` policy.
- All other directives that are **not present** in a specific header part are **"opened"** using a predefined mapping. This prevents a header part from blocking a resource that is explicitly allowed in another header part.

The "opened" values used are:
- `script-src`: `* data: blob: 'unsafe-inline' 'unsafe-eval'`
- `style-src`: `* data: blob: 'unsafe-inline'`
- Others: `* data: blob:`

Example:
If Header 1 contains `img-src site1.com`, and Header 2 contains `img-src site2.com`, the browser would normally block both (since `site1.com` is not in Header 2 and `site2.com` is not in Header 1). By "opening" `img-src` in Header 1 to include `* data: blob:` and doing the same in Header 2, the combined effect correctly allows both `site1.com` and `site2.com`.

---

## Credits

The CSP header splitting logic is based on the work by **basecom** in the [magento2-csp-split-header](https://github.com/basecom/magento2-csp-split-header/tree/main) module.

---

## Support

For further information, contact us:
- by email: hello@bird.eu
- or by form: [https://black.bird.eu/en/contacts/](https://black.bird.eu/contacts/)

---

## Authors

- **Blackbird Team** - *Maintainer* - [They're awesome!](https://github.com/blackbird-agency)

---

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE.txt) file for details.

---

***That's all folks!***
