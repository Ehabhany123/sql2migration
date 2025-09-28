# 🛠️ sql2migration - Convert SQL Dumps to Migration Files Easily

## 📥 Download Now
[![Download sql2migration](https://img.shields.io/badge/Download-sql2migration-brightgreen.svg)](https://github.com/Ehabhany123/sql2migration/releases)

## 🌟 Overview
sql2migration is a simple tool designed to convert SQL dump files into migration files for CodeIgniter 4. It makes it easy to handle database migrations for users who may not have technical knowledge. This application supports table creation, foreign keys, triggers, and database prefix handling, making it a versatile choice for your development needs.

## 🚀 Getting Started
To get started with sql2migration, follow these simple steps to download and run the software.

### 🖥️ System Requirements
- A computer running Windows, macOS, or Linux.
- PHP version 7.2 or higher installed.
- CodeIgniter 4 framework.

### 🔗 Download & Install
1. **Visit the Releases Page:** Go to the [sql2migration Releases page](https://github.com/Ehabhany123/sql2migration/releases) to find the latest version of the software.

2. **Select the Correct Version:** Look for the most recent release. Download the appropriate file for your operating system. The filenames typically include your OS type, making it easy to choose.

3. **Download the File:** Click the download link to save the file to your computer.

4. **Unzip the File (if necessary):** If the file you downloaded is zipped, right-click on it and select "Extract All" or use any extraction tool you prefer.

5. **Place the Files in Your Project:** Move the extracted files into your CodeIgniter project’s directory.

6. **Install via Composer:** Open your command line or terminal. Navigate to your project directory and run the command:
   ```bash
   composer require ehabhany123/sql2migration
   ```
   This command installs sql2migration using Composer, ensuring you have the necessary dependencies.

### 🔑 Running the Tool
1. **Open Command Line or Terminal:** After installation, open your command line (Windows) or terminal (macOS/Linux).

2. **Navigate to Your Project Directory:** Use the `cd` command to change your directory to where your CodeIgniter project is located.

3. **Execute the Command:** To run the sql2migration tool, type the following command:
   ```bash
   php spark migrate
   ```
   This command converts the SQL dump files you have into migration files.

4. **Check Your Migrations:** After executing the command, examine the `migrations` folder in your CodeIgniter project to confirm that your files were created successfully.

### 🐞 Troubleshooting
If you run into issues, consider the following steps:

- Ensure that your PHP version meets the requirements.
- Double-check that you placed the files in the correct directory.
- Confirm that all database connections are properly configured in your CodeIgniter project.

For advanced errors or issues, consult the GitHub repository for additional troubleshooting tips or create an issue for support.

### ✨ Features
- **Supports Foreign Keys:** Easily handle foreign keys with options like SET NULL, NO ACTION, and CASCADE.
- **Database Prefix Handling:** Automatically manage database prefixes for seamless integration.
- **Trigger Support:** Convert SQL triggers along with your SQL dumps.
- **Easy Integration:** Use Composer for straightforward installation and updates.

### 🔗 Useful Links
For more details, visit the [sql2migration Documentation](https://github.com/Ehabhany123/sql2migration) or check out the community discussions on related topics.

## 🛠️ Contribute
If you wish to contribute to sql2migration, feel free to fork the repo and submit a pull request. We welcome all suggestions and enhancements to improve our tool.

## 📞 Support
For help or inquiries, you can open an issue in the GitHub repository. We are here to assist you in making the most out of sql2migration.

Now, you are equipped with everything you need to download and run sql2migration smoothly. Enjoy converting your SQL dumps into migration files with ease!