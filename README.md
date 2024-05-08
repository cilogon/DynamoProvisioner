# AWS DynamoDB Provisioning Plugin

The DynamoDB Provisioning Plugin is designed to provision Registry data into
an Amazon Web Services (AWS) DynamoDB NoSQL database table.

## Understanding DynamoDB

Before instantiating and configuring a provisioner you should have a solid
understanding of [DynamoDB](https://docs.aws.amazon.com/dynamodb/)
including DynamoDB
[types and naming rules](https://docs.aws.amazon.com/amazondynamodb/latest/developerguide/HowItWorks.NamingRulesDataTypes.html).

You should also be familiar with DynamoDB 
[single-table design](https://aws.amazon.com/blogs/compute/creating-a-single-table-design-with-amazon-dynamodb/).
Using the plugin does not require a single-table design and you may
choose to instantiate multiple provisioners writing to multiple tables
for a single CO to accomodate your specific use case, but the plugin is
designed with a single-table approach in mind and specific design choices for
the plugin reflect that approach.

Specifically the plugin is designed for use with a single table where
the partition and (optionally) sort keys may represent different types
of Registry objects and so the keys across items may have vastly different
values and structures.

Additionally the plugin design assumes the deployer will create one or more
Global Secondary Indexes (GSI) as necessary to facillitate queries outside
of those supported by the partition and (optionally) sort key. For example if
the partition key primarily supports queries for CO Person records by
OIDC sub and the deployer also needs to support queries by ePPN then the
deployer may create a GSI on the table using ePPN as the partition key and then
configure (see below) the provisioner with an Additional Attribute Name
and Value Template that uses the ePPN identifier.

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

Other authentication mechanisms supported by the AWS SDK may be implemented
in a future version of the plugin.

## Operations

At this time the plugin only provisions CO Person records and does not
provision CO Groups, Departments, or Services. As noted above, the plugin
is designed in support of single-table approaches and it is expected that
when CO Groups, Departments, and Services are provisioned they will be
provisioned into the same table as the CO Person records using the same
partition and (optional) sort keys.

### Transaction Type

The following Registry transaction types result in the CO Person record
being synchronized to an item in the table:

- Authenticator Updated
- CO Person Added
- CO Person Entered Grace Period
- CO Person Expired
- CO Person Petition Provisioned
- CO Person Pipeline Provisioned
- CO Person Reprovision Requested
- CO Person Unexpired
- CO Person Updated

Note that if a CO Person record has not *yet* been synchronized to an item
in the table and the record has any one of the following status values
then the record is *not* provisioned:

- Confirmed
- Deleted
- Denied
- Duplicate
- Invited
- Pending
- PendingApproval
- PendingConfirmation
- PendingVetting
- Declined

If the Registry transaction type is

```
CO Person Deleted
```

then the item corresponding to the record is deleted from the table.
As a result expunged CO Person records are deleted from the table.

### Updates

Each change in a Registry record results in a full `PUT` of the entire item
to the table. The previous version of the item is overwritten and any
extraneous attributes not written by the provisioner are lost.

### Key Changes

If an identifier value changes such that the partition or sort key for the
item changes then a new item is created with the new key value and the old
item with the old key value is deleted from the table.

## Configuration

Note that the partition key, sort key (if any), and all global secondary
indexes (GSI) for the table must be defined and the table configured before 
the provisioner is able to provision items to the table. The plugin does
not create (nor delete) the table.

### AWS Region

The AWS region in which the DynamoDB table is configured.

### DynamoDB Table Name

The name of the DynamoDB table into which to provision items representing
CO Person records.

### AWS Access Key ID

The AWS Access Key ID with permission to write to the table.

### AWS Secret Access Key

The AWS Secret Access key corresponding tothe AWS Access Key ID.

### Partition Key Attribute Name

The name of the partition key for the table.

### Partition Key Value Template

The template to use to create the partition key value for a CO Person record.
See the [Value Templating](#value-templating) section below for details on
value templating.

### Sort Key Attribute Name

The name of the sort key for the table. A sort key is not required.

### Sort Key Value Template

The template to use to create the sort key value for a CO Person record.
See the [Value Templating](#value-templating) section below for details on
value templating.

### Provision Multiple Items

Whether or not to provision multiple items for a CO Person record when
evaluating the partition and sort key value templates against the set
of Identifiers for the record leads to multiple keys. The attributes
provisioner for each item are the same.

When the box is ticked the provisioner will write and manage multiple
items, one for each unique partition and sort key combination.

If the box is not ticked and the evaluation of the partition and sort key
value templates against the set of Identifiers leads to multiple keys it
is an error and no records will be provisioned.

### Additional Attributes

Up to ten (10) additional attributes beyond the partition and sort key may
be defined. These additional attributes are often used as the partition key
and (optionally) sort key for one or more GSI.

#### Additional Attribute Name

The name of the additional attribute. If used as a partition or sort key
for a GSI the name should match the attribute name used to define the GSI
when creating the GSI.

#### Additional Attribute Value Template

The template to use to create the attribute value.
See the [Value Templating](#value-templating) section below for details on
value templating.

#### Additional Attribute Constraint

An optional constraint that must be satisfied for the attribute value to be
provisioned for the CO Person.

Currently the provisioner supports two (2) types of constraints:

1. *Model Constraint*: the name of a Registry model that must exist for the
   CO Person record. If the record does not include the model then the
   attribute is not provisioned. 

   This functionality can be used to only populate a GSI
   with records that include the model. For example, a GSI where the partition
   key value is taken from the UID Identifier may only be provisioned when the
   SshKey model exists for the record so that a scan of the GSI can be used
   to create all UID and SSH key pairs.

   The following models may be used as a constraint:

   - CoTAndCAgreement
   - CoPersonRole
   - SshKey
   - Url
   - UnixClusterAccount

2. *Identifier Constraint*: the name of an Identifier type that must exist
   for the CO Person record. If the record does not include an Identifier
   of that type then the attribute is not provisioned.

   The constraint is not needed if the Identifier type is already used
   in the attribute value template.

   This functionality can be used to only populate a GSI with records
   when the CO Person record has an Identifier of the specified type.
  
   The format of the constraint is simply the name of the Identifier type.

## Value Templating

The key or attribute value template usually includes syntax so that one
or more values from the Registry models linked to a CO Person record are
substituted to create the value.

At this time the provisioner only supports substituting the value from an
identifier.

The syntax for substituting the value for an identifier is

```
(I/<type>)
```

where `type` is the database type (not the display name) for the identifier.
Extended types may be used.

For example, if the partition key for the provisioner is configured as

```
identifier#EPPN#(I/eppn)
```

and the CO Person record has an Identifier of type `eppn` with value
`skoranda@illinois.edu` then the provisioner will create an item with
partition key value

```
identifier#EPPN#skoranda@illinois.edu
```

A value template may include syntax for more than one identifier type and
may include syntax for the same identifier type more than once.

## Provisioned Item and Attributes

The provisioner will create an item in the table as detailed below.

### Partition Key

The partition key for the item will be the value created using the
Partition Key Value Template. The partition key will be a DynamoDB
String.

### Sort Key

The optional sort key for the item will be the value created using the
Sort Key Value Template. The sort key will be a DynamoDB
String.

### Attributes

- *cm_emailAddresses*: A DynamoDB List of email addresses for the CO Person
record. Each element of the List will be a DynamoDB Map with the following
keys and values:

   - *cm_address*: The email address, a DynamoDB String.
   - *cm_type*: One of `delivery`, `forwarding`, `official`, `personal`,
     `preferred`, `recovery`, or an extended type. The value will be a 
     DynamoDB String.
   - *cm_verified*: A DynamoDB Boolean.

- *cm_identifiers*: A DynamoDB List of identifiers for the CO Person
record. Each element of the List will be a DynamoDB Map with the following
keys and values:

   - *cm_identifier*: The identifier, a DynamoDB String.
   - *cm_login*: A DynamoDB Boolean.
   - *cm_status*: One of `Active` or `Suspended` as a DynamoDB String.
   - *cm_type*: The identifier type, including extended types, as a DynamoDB 
     String.

- *cm_memberships*: A DynamoDB List of CO Group memberships. Each element
of the List will be a DynamoDB Map with the following keys and values:

   - *cm_name*: The name of the CO Group as a DynamoDB String.
   - *cm_owner*: A DynamoDB Boolean indicating whether or not the person is
     an owner of the group.

- *cm_names*: A DynamoDB List of names. Each element of the List will be a
DynamoDB Map with the following keys and values:

   - *cm_family*: The family name as a DynamoDB String.
   - *cm_given*: The given name as a DynamoDB String.
   - *cm_honorific*: The honorific as a DynamoDB String.
   - *cm_middle*: The middle name as a DynamoDB String.
   - *cm_primary_name*: A DynamoDB Boolean indicating whether or not the name
     is the Registry primary name for the CO Person.
   - *cm_suffix*: The suffix as a DynamoDB String.
   - *cm_type*: The name type as a DynamoDB String.

- *cm_roles*: A DynamoDB List of roles. Each element of the List will be a
DynamoDB Map with the following keys and values:

   - *cm_affiliation*: The role affiliation as a DynamoDB String.
   - *cm_cou*: The COU name for the role as a DynamoDB String.
   - *cm_o*: The role organization as a DynamoDB String.
   - *cm_ou*: The role organization unit as a DynamoDB String.
   - *cm_status*: The role status as a DynamoDB String.
   - *cm_title*: The role title as a DynamoDB String.
   - *cm_valid_from*: The role valid from as a DynamoDB String.
   - *cm_valid_through*: The role valid through as a DynamoDB String.

- *cm_sshkeys*: A DynamoDB String Set (SS). Each element of the set will be
a DynamoDB String containing the SSH key type and the key.

- *cm_status*: A DynamoDB String indicating the status for the CO Person
  record.

- *cm_tandc_agreements*: A DynamoDB List of Terms And Condition Agreements. Each
element of the List will be a DynamoDB Map with the following keys and values:

   - *cm_agreement_time*: The Unix time agreement timestamp as a DynamoDB Number.
   - *cm_description*: The description (name) of the Terms and Condition Agreement.
   - *cm_status*: The agreement status as a DynamoDB String.

- *cm_unix_cluster_accounts*: A DynamoDB List of UnixCluster accounts. Each
element of the List will be a DynamoDB Map with the following keys and values:

   - *cm_description*: The name of the UnixCluster object as a DynamoDB String.
   - *cm_gecos*: The GECOS field as a DynamoDB String.
   - *cm_home_directory*: The home directory as a DynamoDB String.
   - *cm_login_shell*: The login shell as a DynamoDB String.
   - *cm_uid*: The UID as a DynamoDB Number.
   - *cm_username*: The username as a DynamoDB String.

### Additional Attributes

The attribute for the item will be the value created using the
Additional Attribute Value Template. The value will be a DynamoDB
String.

### Example

Below is an example item provisioned by the plugin in the JSON format
returned by the DynamoDB API as part of a GET operation.

```json
{
  "PartitionKey": {
    "S": "identifier#oidcsub#http://cilogon.org/serverT/users/27326098"
  },
  "accessid": {
    "S": "identifier#accessid#korandas"
  },
  "cm_emailAddresses": {
    "L": [
      {
        "M": {
          "cm_address": {
            "S": "skoranda@illinois.edu"
          },
          "cm_type": {
            "S": "official"
          },
          "cm_verified": {
            "BOOL": true
          }
        }
      },
      {
        "M": {
          "cm_address": {
            "S": "skoranda@illinois.edu"
          },
          "cm_type": {
            "S": "official"
          },
          "cm_verified": {
            "BOOL": true
          }
        }
      }
    ]
  },
  "cm_identifiers": {
    "L": [
      {
        "M": {
          "cm_identifier": {
            "S": "skoranda@illinois.edu"
          },
          "cm_login": {
            "BOOL": false
          },
          "cm_status": {
            "S": "Active"
          },
          "cm_type": {
            "S": "eppn"
          }
        }
      },
      {
        "M": {
          "cm_identifier": {
            "S": "urn:mace:incommon:uiuc.edu!https://cilogon.org/shibboleth!3fsr8Q7777777770fjwaXGRnFVR8="
          },
          "cm_login": {
            "BOOL": false
          },
          "cm_status": {
            "S": "Active"
          },
          "cm_type": {
            "S": "eptid"
          }
        }
      },
      {
        "M": {
          "cm_identifier": {
            "S": "http://cilogon.org/serverT/users/27326098"
          },
          "cm_login": {
            "BOOL": false
          },
          "cm_status": {
            "S": "Active"
          },
          "cm_type": {
            "S": "oidcsub"
          }
        }
      },
      {
        "M": {
          "cm_identifier": {
            "S": "http://cilogon.org/serverT/users/27326098"
          },
          "cm_login": {
            "BOOL": false
          },
          "cm_status": {
            "S": "Active"
          },
          "cm_type": {
            "S": "sorid"
          }
        }
      },
      {
        "M": {
          "cm_identifier": {
            "S": "10004"
          },
          "cm_login": {
            "BOOL": false
          },
          "cm_status": {
            "S": "Active"
          },
          "cm_type": {
            "S": "messnumber"
          }
        }
      },
      {
        "M": {
          "cm_identifier": {
            "S": "MESS10004"
          },
          "cm_login": {
            "BOOL": false
          },
          "cm_status": {
            "S": "Active"
          },
          "cm_type": {
            "S": "messid"
          }
        }
      },
      {
        "M": {
          "cm_identifier": {
            "S": "skoranda"
          },
          "cm_login": {
            "BOOL": false
          },
          "cm_status": {
            "S": "Active"
          },
          "cm_type": {
            "S": "uid"
          }
        }
      },
      {
        "M": {
          "cm_identifier": {
            "S": "5000"
          },
          "cm_login": {
            "BOOL": false
          },
          "cm_status": {
            "S": "Active"
          },
          "cm_type": {
            "S": "uidnumber"
          }
        }
      },
      {
        "M": {
          "cm_identifier": {
            "S": "5000"
          },
          "cm_login": {
            "BOOL": false
          },
          "cm_status": {
            "S": "Active"
          },
          "cm_type": {
            "S": "gidnumber"
          }
        }
      },
      {
        "M": {
          "cm_identifier": {
            "S": "korandas"
          },
          "cm_login": {
            "BOOL": false
          },
          "cm_status": {
            "S": "Active"
          },
          "cm_type": {
            "S": "accessid"
          }
        }
      }
    ]
  },
  "cm_memberships": {
    "L": [
      {
        "M": {
          "cm_name": {
            "S": "CO:members:all"
          },
          "cm_owner": {
            "BOOL": false
          }
        }
      },
      {
        "M": {
          "cm_name": {
            "S": "CO:members:active"
          },
          "cm_owner": {
            "BOOL": false
          }
        }
      },
      {
        "M": {
          "cm_name": {
            "S": "CO:COU:Injection Team:members:active"
          },
          "cm_owner": {
            "BOOL": false
          }
        }
      },
      {
        "M": {
          "cm_name": {
            "S": "CO:COU:Injection Team:members:all"
          },
          "cm_owner": {
            "BOOL": false
          }
        }
      },
      {
        "M": {
          "cm_name": {
            "S": "skoranda UnixCluster Group"
          },
          "cm_owner": {
            "BOOL": true
          }
        }
      }
    ]
  },
  "cm_names": {
    "L": [
      {
        "M": {
          "cm_family": {
            "S": "Koranda"
          },
          "cm_given": {
            "S": "Scott"
          },
          "cm_primary_name": {
            "BOOL": false
          },
          "cm_type": {
            "S": "official"
          }
        }
      },
      {
        "M": {
          "cm_family": {
            "S": "Koranda"
          },
          "cm_given": {
            "S": "Scott"
          },
          "cm_primary_name": {
            "BOOL": false
          },
          "cm_type": {
            "S": "official"
          }
        }
      },
      {
        "M": {
          "cm_family": {
            "S": "Koranda"
          },
          "cm_given": {
            "S": "Scott"
          },
          "cm_primary_name": {
            "BOOL": true
          },
          "cm_type": {
            "S": "official"
          }
        }
      }
    ]
  },
  "cm_roles": {
    "L": [
      {
        "M": {
          "cm_affiliation": {
            "S": "member"
          },
          "cm_cou": {
            "S": "Injection Team"
          },
          "cm_status": {
            "S": "Active"
          }
        }
      }
    ]
  },
  "cm_sshkeys": {
    "SS": [
      "ecdsa-sha2-nistp521 AAAAE2VjZHNhLXNoYTMjEAAACFBAA07mdTqMbEJBJCCVfV73peiMqlA0yPV8VrYYYYYYYYYOkSOfv5uwEHscA4Jzw+4lUmyNej6+U14wCM4AcSlsHo+uwAiKBdHWVy3xtSy18qzXvB4lIqGjriFSpDWEQzWPDbxRWGRIfWdtRRaOOpa6bqeFTJz5eI3FDhobRAKq6dZnghBwA=="
    ]
  },
  "cm_status": {
    "S": "Active"
  },
  "cm_tandc_agreements": {
    "L": [
      {
        "M": {
          "cm_agreement_time": {
            "N": "1715165404"
          },
          "cm_description": {
            "S": "Acceptable Use Policy"
          },
          "cm_status": {
            "S": "Active"
          }
        }
      }
    ]
  },
  "cm_unix_cluster_accounts": {
    "L": [
      {
        "M": {
          "cm_description": {
            "S": "Primary UnixCluster"
          },
          "cm_gecos": {
            "S": "Scott Koranda"
          },
          "cm_home_directory": {
            "S": "/home/skoranda"
          },
          "cm_login_shell": {
            "S": "/bin/bash"
          },
          "cm_status": {
            "S": "Active"
          },
          "cm_uid": {
            "N": "5000"
          },
          "cm_username": {
            "S": "skoranda"
          }
        }
      }
    ]
  },
  "sshkey_accessid": {
    "S": "korandas"
  }
}
```



