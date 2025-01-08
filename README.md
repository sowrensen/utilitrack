UtiliTrack
----------

UtiliTrack is a simple utility expense tracker application.

1. [Installation](#installation)
2. [Configuration](#configuration)
    * [Database](#database)
    * [Queue](#queue)
    * [Filament](#filament)
    * [Google Sheets](#google-sheets)
3. [Usage](#usage)
    * [Categories](#categories)
    * [Expenses](#expenses)
        + [Import from file](#import-from-file)
        + [Export to file](#export-to-file)
        + [New entry](#new-entry)
4. [Disclaimer](#disclaimer)

## Installation

Clone the repository and install the dependencies:

```bash
git clone https://github.com/sowrensen/utilitrack.git
composer install --no-dev
```

Copy the `.env.example` file to `.env`.

## Configuration

### Database

You should configure a database connection. Either `SQLite` or `MySQL` is fine. Once that is done, you can run the
migrations.

```bash
php artisan migrate
```

There is also a seeder for `Category`. It has four default categories, if you want them, append `--seed` to migration
command. You can seed later as well by running,

```bash
php artisan db:seed --class=CategorySeeder
```

### Queue

Set the `QUEUE_CONNECTION` key to `sync` or `database`. `sync` is recommended if you won't be
importing/exporting large amount of data.

### Filament

The app is built using Filament. You should configure the following keys in `.env` to access the dashboard.

```dotenv
FILAMENT_PATH=
FILAMENT_DOMAIN=
```

- `FILAMENT_PATH`is used if you want to access the admin panel via a route path e.g. `http://utilitrack.test/admin`.
- If you want to access via a subdomain or the domain itself, set `FILAMENT_DOMAIN` instead.

> [!IMPORTANT]
> Keep `FILAMENT_PATH` blank if you set `FILAMENT_DOMAIN`, else it won't work.

Now, an admin account needs to be created. Run the following command,

```bash
php artisan make:filament-user --name <NAME> --email <EMAIL> --password <PASSWORD>
```

### Google Sheets

We have a feature that can append each entry to Google Sheets. For this you are going to need
a [service account and keys](https://cloud.google.com/iam/docs/service-accounts-create). You should enable the `Drive`
and `Sheets` API scopes. **Add the service account as an editor in your spreadsheet.**

Once that is done, you should set up the following keys in `.env`:

```dotenv
GOOGLE_CLOUD_CONFIG_PATH=absolute/path/to/service-account.json
GOOGLE_SHEET_ID=
```

## Usage

Login using your admin credentials. You will see the dashboard.

![Dashboard](https://github.com/user-attachments/assets/c94738de-76a1-45fe-8eee-103d6ad24ac1)

### Categories

From the **Category** menu you can manage categories. You can create subcategories as well. If you check
`Has usage per day` checkbox, a per day usage will be calculated for each entry in this category.

![New Category](https://github.com/user-attachments/assets/b9afd6ad-99a2-439a-a60c-f63c45e5ea39)

### Expenses

From the **Expense** menu you can manage expenses. There are two filters, `Category` and `Date`. Note that, the
summaries for **Usable**, **Leftover**, and **Usage/day** columns are only visible when `Category` filter
is applied, because they are kinda irrelevant otherwise. A note icon will be visible if entry has any notes.

#### Import from file

Using the `Import from file` option, you can bulk import expenses from a CSV file. An example `csv` file is attached in
the pop-up window which you get upon clicking the button. You will receive a notification when import is complete.

#### Export to file

When you select multiple entries in the table, you can export them to a CSV file from the `Bulk actions` menu. Your
filters will be applied to the exported data. You will get a notification when export is complete and the file can
be downloaded from the notification either in `csv` or `xlsx` format.

![Expenses](https://github.com/user-attachments/assets/665868ba-4566-48e3-9096-efec44a97b27)

#### New entry

The new entry form is pretty straightforward. Just fill in the required fields and submit. `Append to GSheets`
checkbox will appear if you've configured the [Google Sheets](#google-sheets) section.

> [!CAUTION]
> If you update the entry later, your changes will not be reflected in the Google Sheet. Also deleting the
> entry from the app won't remove it from the sheet. However, you can append the entry anytime later from Edit if you
> haven't during creation.

![New Entry](https://github.com/user-attachments/assets/81834835-6016-4fa2-a216-be8bf8267369)

#### Editing entry

While editing an existing entry you can append the row google sheet if you haven't already. For already appended
entries,
a green dot will appear beside the title. Otherwise a gray dot will show. `Append to GSheets` button will appear if
you've
configured the [Google Sheets](#google-sheets) section regardless of appended or not.

![Editing Entry](https://github.com/user-attachments/assets/26c109de-f84c-4cd8-bdf7-1daede193eea)

## Disclaimer

The project is totally meant for personal use. If you need additional features, please fork the repo and do it yourself.
Bug repos are always welcome.
