# Paylabs Magento 2 Payment Gateway

[![License](https://github.com/paylabs/paylabs-magento2/blob/main/LICENSE)](LICENSE)

This is the **Paylabs Payment Gateway Module for Magento 2**, connect **Paylabs** services with your store easily.

## Features
- Accept payments via **Paylabs** in Magento 2.
- Automatic payment status updates.
- Secure and reliable payment processing.

---

## 📌 Requirements
- **Magento 2.4.x**
- **PHP 8.x**
- **Paylabs Account** ([Register Now!](https://merchant.paylabs.co.id/paylabs-register-register.html))

---

## 🔧 Installation

### A. Manual Installation
1. Download the module from [GitHub Releases](https://github.com/paylabs/paylabs-magento2/releases/tag/v1.0).
2. Extract and copy the files into:
   ```
   app/code/Paylabs/Payment/
   ```
3. Run the following Magento CLI commands:
   ```sh
   bin/magento module:enable Paylabs_Payment
   bin/magento setup:upgrade
   bin/magento cache:flush
   ```

---

## ⚙️ Configuration
1. Log in to Magento **Admin Panel**.
2. Navigate to:  
   **Stores** → **Configuration** → **Sales** → **Payment Methods**.
3. Find **Paylabs Payment Gateway** and click Configure.
4. Configure the following:
   - **Production Modee**: Choose **No** for Sandbox, or **Yes** for Production
   - **Merchant ID**: *Input Your Merchant ID from Paylabs*
   - **Merchant Private Key**: *Your Private Key* [Generate Key](https://cryptotools.net/rsagen), select *Key Length* 2048
   - **Paylabs Public Key**: *Public key* from paylabs
5. Click **Save Config** and **Flush Cache**.

---

## 📢 Usage
Once configured, customers will see **Paylabs Payment Gateway** as a payment option during checkout. Orders paid via Paylabs will automatically update their status in Magento.

---

## 🛠 Troubleshooting
### 1. "Module Not Found" Error
- Run:
  ```sh
  bin/magento module:status | grep Paylabs_Payment
  ```
  If disabled, enable it using:
  ```sh
  bin/magento module:enable Paylabs_Payment
  ```

### 2. Cache Issues
Try clearing the Magento cache:
```sh
bin/magento cache:flush
bin/magento cache:clean
```

---

## 📝 License
This project is licensed under the [MIT License](LICENSE).

---

## 🤝 Contributing
We welcome contributions! Please fork the repository and submit a pull request.
