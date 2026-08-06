# AWS DynamoDB Provisioning Plugin

The DynamoDB Provisioning Plugin is designed to provision Registry data into
an Amazon Web Services (AWS) DynamoDB NoSQL database table.

## Version

This is the COmanage Registry PE (CakePHP 4) version of the DynamoProvisioner plugin.

- `v1.0.0`: compatible with Registry PE **5.1.0**
- `v2.0.0`: compatible with Registry PE **5.2.0+**

## Understanding DynamoDB

Before instantiating and configuring a provisioner you should have a solid
understanding of [DynamoDB](https://docs.aws.amazon.com/dynamodb/)
including DynamoDB
[types and naming rules](https://docs.aws.amazon.com/amazondynamodb/latest/developerguide/HowItWorks.NamingRulesDataTypes.html).

You should also be familiar with DynamoDB 
[single-table design](https://aws.amazon.com/blogs/compute/creating-a-single-table-design-with-amazon-dynamodb/).
Using the plugin does not require a single-table design and you may
choose to instantiate multiple provisioners writing to multiple tables
for a single CO to accommodate your specific use case, but the plugin is
designed with a single-table approach in mind and specific design choices for
the plugin reflect that approach.

## Authentication

Currently the provisioner supports authenticating to the DynamoDB endpoint
using an Access Key and Secret. The AWS IAM User to which the Access Key
is mapped must have an attached IAM Policy with Effect `Allow` and Actions

- `dynamodb:GetItem`
- `dynamodb:Scan`
- `dynamodb:Query`
- `dynamodb:DeleteItem`
- `dynamodb:PutItem`
- `dynamodb:UpdateItem`

It is recommended that a unique IAM User is created for each plugin
instantiation and that the Policy Resource be scoped to the single
Arn for the DynamoDB table for which the instantiated provisioner is
configured.

## Operations

At this time the plugin only provisions Person records and does not
provision Groups, Departments, or Services.

## Configuration

### AWS Region

The AWS region in which the DynamoDB table is configured.

### DynamoDB Table Name

The name of the DynamoDB table into which to provision items representing
Person records.

### AWS Access Key ID

The AWS Access Key ID with permission to write to the table.

### AWS Secret Access Key

The AWS Secret Access key corresponding to the AWS Access Key ID.

### Partition Key Attribute Name

The name of the partition key for the table.

### Partition Key Value Template

The template to use to create the partition key value for a Person record.
See the Value Templating section for details on value templating.

### Sort Key Attribute Name

The name of the sort key for the table. A sort key is not required.

### Sort Key Value Template

The template to use to create the sort key value for a Person record.
See the Value Templating section for details on value templating.

### Provision Multiple Items

Whether or not to provision multiple items for a Person record when
evaluating the partition and sort key value templates against the set
of Identifiers for the record leads to multiple keys.

### Additional Attributes

Up to ten (10) additional attributes beyond the partition and sort key may
be defined. These additional attributes are often used as the partition key
and (optionally) sort key for one or more Global Secondary Indexes (GSI).

## Value Templating

The key or attribute value template usually includes syntax so that one
or more values from the Registry models linked to a Person record are
substituted to create the value.

At this time the provisioner only supports substituting the value from an
identifier.

The syntax for substituting the value for an identifier is:

```
(I/<type>)
```

where `type` is the identifier type value.

For example, if the partition key for the provisioner is configured as

```
identifier#EPPN#(I/eppn)
```

and the Person record has an Identifier of type `eppn` with value
`user@example.edu` then the provisioner will create an item with
partition key value

```
identifier#EPPN#user@example.edu
```

## Installation (Registry PE 5.2.0+)

1. Place the plugin in `local/plugins/DynamoProvisioner`
2. Run `composer install` in the plugin directory to install AWS SDK dependencies
   (do **not** add `cakephp/cakephp` to this plugin's `composer.json` — Registry
   already provides CakePHP, and a second copy in the plugin `vendor/` will conflict)
3. Activate the plugin via the COmanage CO Configuration > Plugins interface
   (Registry discovers local plugins and updates PSR-4 autoload paths at runtime;
   do not add the plugin to `app/composer.json`)
4. Create a Provisioning Target and select "DynamoProvisioner.DynamoProvisioners" as the plugin

## License

Apache License, Version 2.0





