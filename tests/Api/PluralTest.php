<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api;

use Ecodev\Felix\Api\Plural;
use PHPUnit\Framework\TestCase;

class PluralTest extends TestCase
{
    /**
     * @dataProvider providerMake
     */
    public function testMake(string $input, string $expected): void
    {
        self::assertSame($expected, Plural::make($input));
    }

    public function providerMake(): iterable
    {
        yield ['Account', 'Accounts'];
        yield ['AccountingDocument', 'AccountingDocuments'];
        yield ['Action', 'Actions'];
        yield ['Aggregation', 'Aggregations'];
        yield ['AggregationSet', 'AggregationSets'];
        yield ['Answer', 'Answers'];
        yield ['AntiqueName', 'AntiqueNames'];
        yield ['Artist', 'Artists'];
        yield ['AttributeDefinition', 'AttributeDefinitions'];
        yield ['AttributeValue', 'AttributeValues'];
        yield ['AuditNote', 'AuditNotes'];
        yield ['AuditNoteContainerInterface', 'AuditNoteContainerInterfaces'];
        yield ['AutomaticStateStampInterface', 'AutomaticStateStampInterfaces'];
        yield ['BelongChecklistInterface', 'BelongChecklistInterfaces'];
        yield ['BelongGroupInterface', 'BelongGroupInterfaces'];
        yield ['BelongOrganizationInterface', 'BelongOrganizationInterfaces'];
        yield ['BelongShopInterface', 'BelongShopInterfaces'];
        yield ['Bookable', 'Bookables'];
        yield ['BookableMetadata', 'BookableMetadatas'];
        yield ['BookableTag', 'BookableTags'];
        yield ['Booking', 'Bookings'];
        yield ['Calendar', 'Calendars'];
        yield ['Card', 'Cards'];
        yield ['Cart', 'Carts'];
        yield ['CartLine', 'CartLines'];
        yield ['Change', 'Changes'];
        yield ['Chapter', 'Chapters'];
        yield ['Checklist', 'Checklists'];
        yield ['ChecklistDocument', 'ChecklistDocuments'];
        yield ['ChecklistGroup', 'ChecklistGroups'];
        yield ['City', 'Cities'];
        yield ['Cluster', 'Clusters'];
        yield ['Collection', 'Collections'];
        yield ['Comment', 'Comments'];
        yield ['Communication', 'Communications'];
        yield ['ComputableInterface', 'ComputableInterfaces'];
        yield ['Configuration', 'Configurations'];
        yield ['Constraint', 'Constraints'];
        yield ['Control', 'Controls'];
        yield ['Country', 'Countries'];
        yield ['Course', 'Courses'];
        yield ['Dating', 'Datings'];
        yield ['Document', 'Documents'];
        yield ['DocumentInterface', 'DocumentInterfaces'];
        yield ['DocumentNote', 'DocumentNotes'];
        yield ['DocumentType', 'DocumentTypes'];
        yield ['Domain', 'Domains'];
        yield ['Door', 'Doors'];
        yield ['DynamicScale', 'DynamicScales'];
        yield ['EconomicActivity', 'EconomicActivities'];
        yield ['EmailRecipient', 'EmailRecipients'];
        yield ['EmailRecipientInterface', 'EmailRecipientInterfaces'];
        yield ['Equipment', 'Equipments'];
        yield ['Event', 'Events'];
        yield ['ExpenseClaim', 'ExpenseClaims'];
        yield ['Export', 'Exports'];
        yield ['FacilitatorDocument', 'FacilitatorDocuments'];
        yield ['Faq', 'Faqs'];
        yield ['FaqCategory', 'FaqCategories'];
        yield ['File', 'Files'];
        yield ['Folder', 'Folders'];
        yield ['Group', 'Groups'];
        yield ['GroupDocument', 'GroupDocuments'];
        yield ['HasParentInterface', 'HasParentInterfaces'];
        yield ['HasScaleAndThresholdsInterface', 'HasScaleAndThresholdsInterfaces'];
        yield ['Holiday', 'Holidays'];
        yield ['IdentityProvider', 'IdentityProviders'];
        yield ['Image', 'Images'];
        yield ['Indicator', 'Indicators'];
        yield ['IndicatorDocument', 'IndicatorDocuments'];
        yield ['Institution', 'Institutions'];
        yield ['Invoicable', 'Invoicables'];
        yield ['InvoicableLine', 'InvoicableLines'];
        yield ['Legal', 'Legals'];
        yield ['LegalReference', 'LegalReferences'];
        yield ['Lesson', 'Lessons'];
        yield ['LessonData', 'LessonDatas'];
        yield ['License', 'Licenses'];
        yield ['Log', 'Logs'];
        yield ['Map', 'Maps'];
        yield ['MapCalendar', 'MapCalendars'];
        yield ['MapPlace', 'MapPlaces'];
        yield ['Material', 'Materials'];
        yield ['Message', 'Messages'];
        yield ['News', 'Newses'];
        yield ['NotifiableInterface', 'NotifiableInterfaces'];
        yield ['Objective', 'Objectives'];
        yield ['Order', 'Orders'];
        yield ['OrderLine', 'OrderLines'];
        yield ['Organization', 'Organizations'];
        yield ['OrganizationChecklist', 'OrganizationChecklists'];
        yield ['PaymentMethod', 'PaymentMethods'];
        yield ['Period', 'Periods'];
        yield ['Place', 'Places'];
        yield ['PlaceType', 'PlaceTypes'];
        yield ['Position', 'Positions'];
        yield ['Preset', 'Presets'];
        yield ['Process', 'Processes'];
        yield ['Product', 'Products'];
        yield ['ProductTag', 'ProductTags'];
        yield ['Question', 'Questions'];
        yield ['QuestionSuggestion', 'QuestionSuggestions'];
        yield ['Region', 'Regions'];
        yield ['Report', 'Reports'];
        yield ['Risk', 'Risks'];
        yield ['RiskClassification', 'RiskClassifications'];
        yield ['RiskFrequency', 'RiskFrequencies'];
        yield ['RiskLevel', 'RiskLevels'];
        yield ['RiskMatrix', 'RiskMatrixes'];
        yield ['RiskSeverity', 'RiskSeverities'];
        yield ['Rite', 'Rites'];
        yield ['RoleContextInterface', 'RoleContextInterfaces'];
        yield ['Scale', 'Scales'];
        yield ['ScaleLike', 'ScaleLikes'];
        yield ['Section', 'Sections'];
        yield ['Session', 'Sessions'];
        yield ['Setting', 'Settings'];
        yield ['Sheet', 'Sheets'];
        yield ['SheetCalendar', 'SheetCalendars'];
        yield ['SheetOutput', 'SheetOutputs'];
        yield ['Shift', 'Shifts'];
        yield ['ShiftTag', 'ShiftTags'];
        yield ['Shop', 'Shops'];
        yield ['Site', 'Sites'];
        yield ['SiteChecklist', 'SiteChecklists'];
        yield ['State', 'States'];
        yield ['Statistic', 'Statistics'];
        yield ['StockMovement', 'StockMovements'];
        yield ['Subscription', 'Subscriptions'];
        yield ['SubscriptionForm', 'SubscriptionForms'];
        yield ['Suggestion', 'Suggestions'];
        yield ['Tag', 'Tags'];
        yield ['Task', 'Tasks'];
        yield ['Taxonomy', 'Taxonomies'];
        yield ['Theme', 'Themes'];
        yield ['Thesaurus', 'Thesauruses'];
        yield ['Threshold', 'Thresholds'];
        yield ['Timelog', 'Timelogs'];
        yield ['Transaction', 'Transactions'];
        yield ['TransactionLine', 'TransactionLines'];
        yield ['TransactionTag', 'TransactionTags'];
        yield ['User', 'Users'];
        yield ['UserCalendar', 'UserCalendars'];
        yield ['UserGroup', 'UserGroups'];
        yield ['UserOrganization', 'UserOrganizations'];
        yield ['UserPlace', 'UserPlaces'];
        yield ['UserSite', 'UserSites'];
        yield ['UserTag', 'UserTags'];
    }
}
