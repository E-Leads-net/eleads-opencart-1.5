# eleads-opencart-1.5

Public **E-Leads module for OpenCart 1.5** that allows exporting product data as
structured feeds and integrating the store catalog with the E-Leads platform.

The module is intended to be used **only as part of the E-Leads ecosystem**.

At the current stage, the module operates in **feed-only mode**.
Full product synchronization is planned in future releases.

---

## 🚀 Features

- ✅ Export products from OpenCart 1.5
- ✅ Generate structured product feeds (XML / JSON)
- ✅ Integration with the E-Leads platform
- ✅ Optimized for large catalogs
- ⏳ Full bidirectional synchronization (planned)

---

## 🧩 Supported CMS

- OpenCart **1.5.x**

---

## 🔄 Current Mode

**Feed-only**

The module currently supports:
- full product catalog export
- category hierarchy export
- prices, images, attributes, and stock data
- data normalization on the E-Leads side

---

## 🛠 Installation

Standard OpenCart 1.5 module installation.

### 1️⃣ Upload files
Copy the contents of the `upload/` directory to your OpenCart root directory:

/admin
/catalog
/system


### 2️⃣ Install the module
1. Go to **Admin → Extensions → Modules**
2. Find **E-Leads Feed**
3. Click **Install**
4. Click **Edit** to configure the module

### 3️⃣ Configure
In the module settings:
- enable the module
- configure access key (if required)
- save settings

---

## 🔗 Feed URL

After installation and activation, the product feed will be available at:

https://your-store.com/index.php?route=feed/eleads
If SEO URLs are enabled:
https://your-store.com/eleads-feed


> The feed URL is used by the E-Leads platform to import and process product data.

---

## 🛣️ Roadmap

- **v1.x** — Product feed export (current)
- **v2.x** — Full product synchronization
- **v3.x** — Incremental sync & webhooks

---

## 🔐 License & Usage

This is a **public repository**.

✅ Allowed:
- install and use the module to export data **to the E-Leads platform**

❌ Not allowed:
- use with third-party services
- modify and redistribute for non–E-Leads integrations

---

## 🔗 E-Leads Ecosystem

- `dashboard.e-leads.net` — project and widget management
- `api.e-leads.net` — API gateway
- `processing.e-leads.net` — data normalization and processing

---

## 📬 Support

This module is provided as part of the E-Leads ecosystem.
For support and integration questions, contact the E-Leads team.
