ta: Bank Transaction Analyser
==============================

Currently only works for FNB csv files

Installation:
-------------
1) cd ~/somewhere

2) git clone git@github.com:rishadomar/ta.git

3) cd ta

4) md reports

5) cp example_categories.list categories.list

6) sqlite3 ta.db
CREATE TABLE trans (
  ID INTEGER PRIMARY KEY,
  trdate,
  description,
  amount,
  category
, status, comment);


7) download your csv into some folder
For FNB this is the route on their website:

 * My Bank Accounts

 * Select Account

 * Click on Available balance

 * Orange Menu + Search

 * Custom

 * Download in csv format

Then extract the csv file into a folder

8) Import your csv file. Duplicates are ignored
./ta import downloads/2014-01-23.csv

9) Generate a report
./ta report jan2014 >reports/jan2014.txt
vim reports/jan2014.txt

