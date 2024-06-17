# How to Use the `ModelMerge` Class

The `MergeModels` facade is the primary way to interact with the `ModelMerge` class. This class provides a simple and flexible way to merge two models in a Laravel
application.

Below is an example script that merges two contacts into a single contact record.

```php
use EncoreDigitalGroup\MergeModels\MergeModels;
use App\Models\Contact;

$originalContact = Contact::find(1);
$duplicateContact = Contact::find(2);

MergeModels::setBaseModel($originalContact)->setDuplicateModel($duplicateContact)->unifyOnBase();
```

## What does this script do?

1. We locate the original contact by its ID. In this case 1.
2. We locate the duplicate contact by its ID. In this case 2.
3. We use the MergeModels facade and inform the Merger that the base model is contact 1 and the duplicate model is contact 2.
4. We then tell the Merger to unify these two contacts into a single record, in this case the base model (contact 1).

## What about merging relationships?

MergeModels supports merging HasMany relationships as well. Let's say that your contact model has a relationship defined for the contact's email. Since
contacts can have multiple emails, it's a HasMany relationship named `emailAddresses`. To merge the duplicate contacts `emailAddresses` relationship
into the base contact, we would adjust the Merger as follows:

```php
MergeModels::setBaseModel($baseContact)->setDuplicateModel($duplicateContact)->withRelationships(['emailAddresses'])->unifyOnBase();
```

Now all email addresses associated with the duplicate contact have been transferred to the base contact.