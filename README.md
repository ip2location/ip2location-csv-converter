# IP2Location CSV Converter

This PHP script converts IP2Location CSV data file, that contains the IP address in numeric notation, into dot-decimal notation (such as x.x.x.x) or CIDR notation (x.x.x.x/24). It supports both the IP2Location commercial edition, DB1 to DB24 database and also the free edition, IP2Location LITE database. In addition to this, this converter can also be used to convert any CSV file that contains the IP number (the numeric notation).

You can download the IP2Location CSV file at the below links:  
[IP2Location Commercial Database](https://www.ip2location.com)   
[IP2Location LITE Database](https://lite.ip2location.com)  

Please do not use this script to convert IP2Location BIN data file. It only support the CSV format, not the binary format.

## Usage

``` bash
php ip2location-csv-converter.php [-range | -cidr] [-replace | -append] INPUT_FILE OUTPUT_FILE
```



#### Parameters

| Parameter | Description                                                  |
| --------- | ------------------------------------------------------------ |
| -range    | IP numbers will be converted into the first IP address and last IP address in the range. |
| -cidr     | IP numbers will be converted into CIDR format.               |
| -replace  | The IP numbers in will be replaced to the selected format.   |
| -append   | The converted format will be appended after the IP numbers field. |



**Example:**

```
php ip2location-csv-converter.php -range -replace IP2LOCATION-DB1.CSV IP2LOCATION-DB1.NEW.CSV
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

## Custom Input File
You can use this converter for a custom input file provided the input is in CSV format, with the first and second field contain the **ip from** and **ip to** information in numeric format.

## Support
URL: https://www.ip2location.com
