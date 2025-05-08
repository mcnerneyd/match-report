
# Building
First you need fuelphp-1.8.2.zip:

    wget http://fuelphp.com/files/download/42 -O fuelphp-1.8.2.zip
    echo "4f7de4eda568300fbc0a79f0e108617481699716845f2f05dbec5c6145192227  fuelphp-1.8.2.zip" | sha256sum -c -

# Notes

If throwing exceptions:

  There is no security.output_filter defined in your application config file

it means an exception is being thrown before the exception can be handled.

Check the error log.

* Potential error in Sentry declaration


## Python/Robot Framework

Virtual environments: https://realpython.com/python-virtual-environments-a-primer/

## Testing

Unit Tests:
    cd admin
    vendor/bin/pest

Functional Testing:
    cd test/
