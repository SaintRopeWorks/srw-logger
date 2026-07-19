# SRWLogger

A zero-configuration, performance-optimized, stack-aware logging engine for WordPress and PHP 8.1+ environments. Designed to act as a self-cleaning file-writing utility, it leverages PHP 8 attributes to dynamically route application telemetry across isolated filesystem structures based on runtime code execution traces.

## 🚀 Key Capabilities

### 1. Zero-Configuration Auto-Naming Engine
You do not need to manually instantiate or name log targets. When you fire a basic logging macro (`SRW_Logger::info`), the engine climbs your thread's active execution stack. It automatically identifies the named class, static method, or global function that called it, generates a safe filename block (e.g., `MyClass_myMethod.log`), and dumps the string to disk.

### 2. Stack-Aware Telemetry Tracer
The engine aggressively climbs past anonymous closures, lambdas, recursive loop helpers, and standard internal file array maps (`array_map`, `call_user_func`). It isolates and prints the true origin context—the actual file basename and line number where your application logic kicked off the telemetry sweep.

### 3. Dynamic Log Families (`#[LogFamily]`)
Log Families allow you to cross-post or aggregate tracking data from individual component execution blocks into high-level tracking files. 

By decorating a function with `#[LogFamily]`, any log statement fired *inside* that function's execution lifecycle will write to its individual auto-named file, and automatically cascade an identical copy out to the family log stream target.

- **Isolation Routing:** Families support custom relative paths (e.g., writing to a `siblings/` subfolder) or absolute filesystem targets.
- **Repeatable Stacking:** You can attach multiple `#[LogFamily]` attributes to a single method to split a single log line across multiple separate data pipelines simultaneously.
- **Dynamic Resolution:** Family names can be resolved at runtime from scalar strings, global variables (`$GLOBALS`), WordPress options table values, or constant flags.

---

## 🛠️ Core Installation & Initialization

Drop the plugin folder into your WordPress Must-Use plugins directory:
```text
wp-content/mu-plugins/
├── srw-logger.php            # Core global bootloader loader script
└── Logger/                   # Main plugin asset architecture directory
```

Initialize the global storage configurations inside your project setup pass:
```php
// Configures target directories, file appends, roll sizes (2MB default), and message chunk bounds
SRW_Logger::setup(WP_CONTENT_DIR . '/uploads/srw-enterprise-logs');
```

---

## 💻 Code Integration Handbook

### Basic Logging Macros
The framework exposes four basic public macros. Each supports standard message strings or deep data payload objects (which are automatically formatted into structured, pretty-printed JSON arrays):

```php
use SRW\Logger\Public\Classes\SRW_Logger;

// Basic text logging
SRW_Logger::info("Layout template tree grids initialized cleanly.");

// Logging with deep object tracking datasets
$debug_payload = ['id' => 7, 'status' => 'pending', 'tax' => 0.08];
SRW_Logger::warn("Checking financial calculation variables matrix.", $debug_payload);

SRW_Logger::error("Remote API gateway transaction connection timeout.");
SRW_Logger::verbose("Trace execution checkpoint loop completed.");
```

### Leveraging Log Families
Decorate your theme layout hooks or integration lambdas with attributes to capture aggregated traces:

```php
use SRW\Logger\Public\Attributes\LogFamily;
use SRW\Logger\Public\Classes\SRW_Logger;

class My_Theme_Router {

    // 1. Static Resolution: Duplicates logs into 'checkout_vault.log'
    #[LogFamily('checkout_vault')]
    public function process_user_payment() {
        SRW_Logger::info("Handshaking with payment gateway."); // Writes to My_Theme_Router_process_user_payment.log AND checkout_vault.log
    }

    // 2. Relative Subfolders: Writes into 'wp-content/uploads/srw-enterprise-logs/integrations/v3/stripe.log'
    #[LogFamily('stripe', 'integrations/v3')]
    public function sync_stripe_webhooks() {
        SRW_Logger::verbose("Webhook event packet verified.");
    }

    // 3. Dynamic Option Resolution: Resolves the log filename from get_option('my_dynamic_field_key')
    #[LogFamily('my_dynamic_field_key', null, 'option')]
    public function executing_dynamic_pipeline() {
        SRW_Logger::warn("Dynamic database loop sweep completed.");
    }

    // 4. Attribute Stacking: Cross-posts a single trace entry out to multiple targets at once
    #[LogFamily('global_ecommerce_tracker')]
    #[LogFamily('analytics_stream', 'metrics/marketing')]
    public function dispatch_conversion_pixels() {
        SRW_Logger::info("Pixel payload fired.");
    }
}
```

---

## ⚙️ Advanced Features

### Automated File Rollover Safeguard
To protect your Namecheap Linux server storage quotas from blowing up, individual stream workers query your parent configuration settings before every single write operation. If a file crosses your designated threshold (default: 2MB), the engine automatically renames the file with an explicit calendar tracking stamp (`-YYYYMMDD-HHMMSS.log`) and instantly opens a fresh, empty stream file block.

### Self-Cleaning Log Purger
During the `setup()` execution pass, a background garbage collection loop automatically scans your log vault directory. It evaluates the file modified timestamps (`filemtime`) of archived, rotated log sheets, and permanently unlinks any file that has crossed your defined retention threshold (default: 14 days), keeping your server pristine with zero cron-job dependencies.

### Excel-Style Format Builder Wizard
Administrators can navigate to the native WordPress **Settings ➜ SRWLogger** menu dashboard frame to manage configurations visually. 
- Rearrange log layouts using an intuitive, rich-text token canvas.
- Drop in pills for system variables like `{SITENAME}`, `{DATE:<format>}`, `{TIME:<format>}`, `{LEVEL}`, `{CONTEXT}`, and `{MESSAGE}`.
- View real-time line generation tracing layouts via the live stream evaluation box underneath before committing changes to the database.
