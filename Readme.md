# DatabaseInspection
Will decorate the PDO connection of an application and log all queries, which might have possible SQL injections in it.

# How does it work?
Every query is intercepted and parsed. In the parsed query, hardcoded constant values (e.g. `SELECT * FROM my_table WHERE value = 'hi'`)
are found and declared as possible SQL injections.

You will find those queries with a stack trace, request infos and more details in a output folder, that you can define.

# How do I use it?
In the application you want to test, find the place, where your PDO connection is created. Most PHP software uses PDO,
also ORMs like Doctrine makes use of it.

## Example
Given you have an application that uses PDO like this:

```
$this->_connection = new PDO(
    $dsn,
    $username,
    $password,
    $options
);
```

with this:

```
use \\Dnoegel\\DatabaseInspection\\PDOInspectionDecorator;

try {
    $this->_connection = new PDOInspectionDecorator(
        new PDO(
            $dsn,
            $username,
            $password,
            $options
        )
    );

    $this->_connection->setProblemInspector(new \\Dnoegel\\DatabaseInspection\\SqlProblemInspector(
            new \\Dnoegel\\DatabaseInspection\\Storage\\JsonStorage('/tmp/sql_inspector_output'),
    ));

```

you can decorate your PDO connection.

So this is actually three steps:

* Include / require the autoloader of this lib. If you installed it via composer into your application, you can skip this.
* Decorate the PDO connection with the `PDOInspectionDecorator`, which acts fully transparent to the PDO connection, so your application will be fine with it.
* Inject an instance of `\Dnoegel\DatabaseInspection\SqlProblemInspector` into the `PDOInspectionDecorator`.
`SqlProblemInspector` has only one mandatory requirement: `JsonStorage`, which you can configure to save your profile files
 to the position you like.

# Whitelisting
If you checked a query and think, that its ok (e.g. values are casted to int beforehands), you can move them to the `whitelist` folder
of your output folder. The script will then not bother you any more.

The whitelisting will figure out the constant parts of the query, so e.g. `SELECT * FROM my_table WHERE value = 'hi'`
and `SELECT * FROM my_table WHERE value = 'another string'` will be the same query from a technical perspective.

# Creating the issue inspector:

```
$parser = new \\Dnoegel\\DatabaseInspection\\SqlProblemInspector(
    new \\Dnoegel\\DatabaseInspection\\Storage\\JsonStorage(),
    new \\Dnoegel\\DatabaseInspection\\RouteProvider\\RouteProvider(),
    new \\Dnoegel\\DatabaseInspection\\Trace\\DebugTrace(),
    true
)
```

The last parameter will decide, if all findings are saved to the `problem` document or if the script should try to separate
"issues" and "problems". "Issues" are findings, that only consist of scalar values, which could still be an SQL injection,
so this is by no means a "all-clear" - just a "check the problems first".

# Reading the "problem" file
Each problem file provides the following info:

* `route`: The route / request, that triggered that query
* `problems`: The list of the static values, in this case `0` - so this can probably be whitelisted
* `code`: The code of the function, that triggered the problematic query
* `sql`: The SQL being executed
* `trace`: The trace of the SQL - so you can tell, which file / which line executed it
* `normalized`: The parsed and normalized SQL query - so all constant values are removed here, to match the same query
with other hardcoded values


```
{
    "route": "\/media\/checkout\/confirm",
    "problems": [
        {
            "expr_type": "const",
            "base_expr": "0",
            "sub_tree": false
        }
    ],
    "code": {
        "999": "    public function sCountBasket()",
        "1000": "    {",
        "1001": "        return $this->db->fetchOne(",
        "1002": "            'SELECT COUNT(*) FROM s_order_basket WHERE modus = 0 AND sessionID = ?',",
        "1003": "            array($this->session->get('sessionId'))",
        "!!!": "        );",
        "1005": "    }"
    },
    "sql": "SELECT COUNT(*) FROM s_order_basket WHERE modus = 0 AND sessionID = ?",
    "trace": {
        "7": "sBasket: sCountBasket:  84",
        "8": "Shopware_Controllers_Frontend_Checkout: postDispatch:  161",
        "9": "Enlight_Controller_Action: dispatch:  524",
        "10": "Enlight_Controller_Dispatcher_Default: dispatch:  227",
        "11": "Enlight_Controller_Front: dispatch:  148",
        "12": "Shopware\\Kernel: handle:  492",
        "13": "Symfony\\Component\\HttpKernel\\HttpCache\\HttpCache: forward:  255",
        "14": "Shopware\\Components\\HttpCache\\AppCache: forward:  449",
        "15": "Symfony\\Component\\HttpKernel\\HttpCache\\HttpCache: fetch:  349",
        "16": "Symfony\\Component\\HttpKernel\\HttpCache\\HttpCache: lookup:  178",
        "17": "Shopware\\Components\\HttpCache\\AppCache: lookup:  213",
        "18": "Symfony\\Component\\HttpKernel\\HttpCache\\HttpCache: handle:  114",
        "19": "Shopware\\Components\\HttpCache\\AppCache: handle:  101"
    },
    "normalized": {
        "SELECT": [
            {
                "expr_type": "aggregate_function",
                "alias": false,
                "base_expr": "COUNT",
                "sub_tree": [
                    {
                        "expr_type": "colref",
                        "base_expr": "*",
                        "sub_tree": false
                    }
                ],
                "delim": false
            }
        ],
        "FROM": [
            {
                "expr_type": "table",
                "table": "s_order_basket",
                "no_quotes": {
                    "delim": false,
                    "parts": [
                        "s_order_basket"
                    ]
                },
                "alias": false,
                "hints": false,
                "join_type": "JOIN",
                "ref_type": false,
                "ref_clause": false,
                "base_expr": "s_order_basket",
                "sub_tree": false
            }
        ],
        "WHERE": [
            {
                "expr_type": "colref",
                "base_expr": "modus",
                "no_quotes": {
                    "delim": false,
                    "parts": [
                        "modus"
                    ]
                },
                "sub_tree": false
            },
            {
                "expr_type": "operator",
                "base_expr": "=",
                "sub_tree": false
            },
            {
                "expr_type": "const",
                "base_expr": "NORMALIZED",
                "sub_tree": false
            },
            {
                "expr_type": "operator",
                "base_expr": "AND",
                "sub_tree": false
            },
            {
                "expr_type": "colref",
                "base_expr": "sessionID",
                "no_quotes": {
                    "delim": false,
                    "parts": [
                        "sessionID"
                    ]
                },
                "sub_tree": false
            },
            {
                "expr_type": "operator",
                "base_expr": "=",
                "sub_tree": false
            },
            {
                "expr_type": "colref",
                "base_expr": "?",
                "no_quotes": {
                    "delim": false,
                    "parts": [
                        "?"
                    ]
                },
                "sub_tree": false
            }
        ]
    }
}
```

# Notice
This is by no mean a security layer. **Do not** use it as such. Its a development tool and a proof of concept for parsing
SQL queries in order to find constant query parts that might be replaced with prepared statements. Do not use it in production.