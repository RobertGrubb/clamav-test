# ClamAV Test

This is a test project for using ClamAV with Github.

## Installing

Please read the following to understand what you need in order for this to work.

### Prereqs

- PHP 8+
- Composer installed
- A server with ClamAV Daemon installed and the port open.

### Installing packages

`composer install`

### Running the script

`php /path/to/project/index.php`

## Example output of successful run:

```
array(4) {
  ["passed"]=>
  int(3)
  ["failed"]=>
  int(0)
  ["files"]=>
  array(3) {
    [0]=>
    array(2) {
      ["file"]=>
      string(47) "/path/files/test1.json"
      ["passed"]=>
      bool(true)
    }
    [1]=>
    array(2) {
      ["file"]=>
      string(47) "/path/files/test2.json"
      ["passed"]=>
      bool(true)
    }
    [2]=>
    array(2) {
      ["file"]=>
      string(52) "/path/files/test/test3.json"
      ["passed"]=>
      bool(true)
    }
  }
  ["logs"]=>
  array(4) {
    [0]=>
    string(50) "Scanner: ClamAV ping returned successful response."
    [1]=>
    string(94) "Scanner: ClamAV is running a scan test against /path/files/test1.json"
    [2]=>
    string(94) "Scanner: ClamAV is running a scan test against /path/files/test2.json"
    [3]=>
    string(99) "Scanner: ClamAV is running a scan test against /path/files/test/test3.json"
  }
}
```
