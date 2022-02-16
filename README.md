# Share import

Import external links from csv to your instance

## Install

* Download this repository
* Put on server on your app folder
* Install the app

## How to use

Create CSV with columns:
```csv
path,token,password,user
```
as
| Field    | Description                           |
| -------- | ------------------------------------- |
| path     | Path of file to create link           |
| token    | **Optional**. Token of file           |
| password | **Optional**. Passord to access link  |
| user     | The user owner of target file of link |

For now only is possible import public links and only with permission to read.

After that you have the CSV file run:

```
occ share_import:import yourFile.csv
```