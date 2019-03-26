# IP2Location CSV Converter

This PHP script converts IP2Location CSV database into IP range or CIDR format. It supports the IP2Location DB1 to DB24 database in CSV format for conversion, including IP2Location LITE database.

Please note that this conversion script doesn't works with IP2Location BIN data file.

You can download the IP2Location CSV file at the below links:  
[IP2Location Commercial Database](https://www.ip2location.com)   
[IP2Location LITE Database](https://lite.ip2location.com)  


## Usage

``` bash
php ip2location-csv-converter.php [-range | -cidr] INPUT_FILE OUTPUT_FILE
```

**Example:**

```
php ip2location-csv-converter.php -range IP2LOCATION-DB1.CSV IP2LOCATION-DB1.NEW.CSV

```

### Sample Input

```
"17170432","17301503","IN","India"
"17301504","17367039","CN","China"
"17367040","17432575","MY","Malaysia"
"17432576","17435135","CN","China"
"17435136","17435391","AU","Australia"
"17435392","17465343","CN","China"
"17465344","17498111","TH","Thailand"
"17498112","17563647","KR","Korea, Republic of"
"17563648","17825791","CN","China"
"17825792","17842175","KR","Korea, Republic of"
```



### Sample Output (Range)

```
"1.6.0.0","1.7.255.255","IN","India"
"1.8.0.0","1.8.255.255","CN","China"
"1.9.0.0","1.9.255.255","MY","Malaysia"
"1.10.0.0","1.10.9.255","CN","China"
"1.10.10.0","1.10.10.255","AU","Australia"
"1.10.11.0","1.10.127.255","CN","China"
"1.10.128.0","1.10.255.255","TH","Thailand"
"1.11.0.0","1.11.255.255","KR","Korea, Republic of"
"1.12.0.0","1.15.255.255","CN","China"
"1.16.0.0","1.16.63.255","KR","Korea, Republic of"
```



### Sample Output (CIDR)

```
"1.6.0.0/15","IN","India"
"1.8.0.0/16","CN","China"
"1.9.0.0/16","MY","Malaysia"
"1.10.0.0/21","CN","China"
"1.10.8.0/23","CN","China"
"1.10.10.0/24","AU","Australia"
"1.10.11.0/24","CN","China"
"1.10.12.0/22","CN","China"
"1.10.16.0/20","CN","China"
"1.10.32.0/19","CN","China"
```

