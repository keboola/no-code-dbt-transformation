# no-code-dbt-transformation

> Fill in description

# Usage

> fill in usage instructions

## Development
 
Clone this repository and init the workspace with following command:

```
git clone https://github.com/keboola/no-code-dbt-transformation
cd no-code-dbt-transformation
cp .env.dist .env
docker-compose build
docker-compose run --rm dev composer install --no-scripts
```

After copying `.env.dist` to `.env`, fill in the required values.

Run the test suite using this command:

```
docker-compose run --rm dev composer tests
```
 
# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/) 
