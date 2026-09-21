-- Generated from SQLite: C:\Users\User\AppData\Local\Aronium\Data\pos.db
-- Schema only: no application data is included.
-- Review type mappings and target naming before applying to production.

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `ApplicationProperty` (
  `Name` VARCHAR(255) NOT NULL,
  `Value` LONGTEXT,
  PRIMARY KEY (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Barcode` (
  `Id` BIGINT,
  `ProductId` BIGINT NOT NULL,
  `Value` LONGTEXT NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Company` (
  `Id` BIGINT NOT NULL,
  `Name` LONGTEXT NOT NULL,
  `Address` LONGTEXT,
  `PostalCode` LONGTEXT,
  `City` LONGTEXT,
  `CountryId` BIGINT NOT NULL,
  `TaxNumber` LONGTEXT,
  `Email` LONGTEXT,
  `PhoneNumber` LONGTEXT,
  `Logo` LONGBLOB,
  `BankAccountNumber` LONGTEXT,
  `BankDetails` LONGTEXT,
  `StreetName` LONGTEXT,
  `AdditionalStreetName` LONGTEXT,
  `BuildingNumber` LONGTEXT,
  `PlotIdentification` LONGTEXT,
  `CitySubdivisionName` LONGTEXT,
  `CountrySubentity` LONGTEXT,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Counter` (
  `Name` VARCHAR(255) NOT NULL,
  `Value` BIGINT NOT NULL,
  PRIMARY KEY (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Country` (
  `Id` BIGINT NOT NULL,
  `Name` LONGTEXT NOT NULL,
  `Code` LONGTEXT,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Currency` (
  `Id` BIGINT NOT NULL,
  `Name` LONGTEXT NOT NULL,
  `Code` LONGTEXT,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Customer` (
  `Id` BIGINT NOT NULL,
  `Code` LONGTEXT,
  `Name` LONGTEXT NOT NULL,
  `TaxNumber` LONGTEXT,
  `Address` LONGTEXT,
  `PostalCode` LONGTEXT,
  `City` LONGTEXT,
  `CountryId` BIGINT,
  `DateCreated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DateUpdated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Email` LONGTEXT,
  `PhoneNumber` LONGTEXT,
  `IsEnabled` TINYINT NOT NULL DEFAULT 1,
  `IsCustomer` TINYINT NOT NULL DEFAULT 1,
  `IsSupplier` TINYINT NOT NULL DEFAULT 1,
  `DueDatePeriod` BIGINT NOT NULL DEFAULT 0,
  `StreetName` LONGTEXT,
  `AdditionalStreetName` LONGTEXT,
  `BuildingNumber` LONGTEXT,
  `PlotIdentification` LONGTEXT,
  `CitySubdivisionName` LONGTEXT,
  `CountrySubentity` LONGTEXT,
  `IsTaxExempt` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `CustomerDiscount` (
  `Id` BIGINT NOT NULL,
  `CustomerId` BIGINT NOT NULL,
  `Type` TINYINT NOT NULL DEFAULT 0,
  `Uid` BIGINT,
  `Value` DOUBLE NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `CustomerDiscount_1` (`CustomerId`, `Type`, `Uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Document` (
  `Id` BIGINT NOT NULL,
  `Number` LONGTEXT NOT NULL,
  `UserId` BIGINT NOT NULL,
  `CustomerId` BIGINT,
  `OrderNumber` LONGTEXT,
  `Date` DATE NOT NULL,
  `StockDate` DATETIME NOT NULL,
  `Total` DECIMAL(20, 6) NOT NULL,
  `IsClockedOut` TINYINT NOT NULL DEFAULT 0,
  `DocumentTypeId` BIGINT NOT NULL,
  `WarehouseId` BIGINT NOT NULL,
  `ReferenceDocumentNumber` LONGTEXT,
  `DateCreated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DateUpdated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `InternalNote` LONGTEXT,
  `Note` LONGTEXT,
  `DueDate` DATE,
  `Discount` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `DiscountType` BIGINT NOT NULL DEFAULT 0,
  `PaidStatus` BIGINT NOT NULL DEFAULT 0,
  `DiscountApplyRule` TINYINT NOT NULL DEFAULT 0,
  `ServiceType` BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `DocumentCategory` (
  `Id` BIGINT NOT NULL,
  `Name` LONGTEXT NOT NULL,
  `LanguageKey` LONGTEXT,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `DocumentItem` (
  `Id` BIGINT NOT NULL,
  `DocumentId` BIGINT NOT NULL,
  `ProductId` BIGINT NOT NULL,
  `Quantity` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `ExpectedQuantity` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `PriceBeforeTax` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `Price` DECIMAL(20, 6) NOT NULL,
  `Discount` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `DiscountType` BIGINT NOT NULL DEFAULT 0,
  `ProductCost` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `PriceBeforeTaxAfterDiscount` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `PriceAfterDiscount` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `Total` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `TotalAfterDocumentDiscount` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `DiscountApplyRule` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `DocumentItemExpirationDate` (
  `DocumentItemId` BIGINT,
  `ExpirationDate` DATE NOT NULL,
  PRIMARY KEY (`DocumentItemId`),
  UNIQUE KEY `IX_DocumentItemExpirationdate` (`DocumentItemId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `DocumentItemTax` (
  `DocumentItemId` BIGINT NOT NULL,
  `TaxId` BIGINT NOT NULL,
  `Amount` DOUBLE NOT NULL DEFAULT 0,
  PRIMARY KEY (`DocumentItemId`, `TaxId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `DocumentType` (
  `Id` BIGINT,
  `Name` LONGTEXT NOT NULL,
  `Code` LONGTEXT NOT NULL,
  `DocumentCategoryId` BIGINT NOT NULL,
  `WarehouseId` BIGINT NOT NULL,
  `StockDirection` TINYINT NOT NULL DEFAULT 0,
  `EditorType` BIGINT NOT NULL DEFAULT 0,
  `PrintTemplate` LONGTEXT,
  `PriceType` BIGINT NOT NULL DEFAULT 0,
  `LanguageKey` LONGTEXT,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `FiscalItem` (
  `PLU` BIGINT NOT NULL,
  `Name` LONGTEXT NOT NULL,
  `VAT` LONGTEXT NOT NULL,
  PRIMARY KEY (`PLU`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `FloorPlan` (
  `Id` BIGINT NOT NULL,
  `Name` LONGTEXT NOT NULL,
  `Color` LONGTEXT NOT NULL DEFAULT 'Transparent',
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `FloorPlanTable` (
  `Id` BIGINT NOT NULL,
  `Name` LONGTEXT NOT NULL,
  `FloorPlanId` BIGINT NOT NULL,
  `PositionX` DOUBLE NOT NULL DEFAULT 0,
  `PositionY` DOUBLE NOT NULL DEFAULT 0,
  `Width` DOUBLE NOT NULL,
  `Height` DOUBLE NOT NULL,
  `IsRound` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `LoyaltyCard` (
  `Id` BIGINT NOT NULL,
  `CustomerId` BIGINT NOT NULL,
  `CardNumber` LONGTEXT,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Migration` (
  `ID` BIGINT NOT NULL,
  `Version` LONGTEXT NOT NULL,
  `Description` LONGTEXT,
  `FileName` LONGTEXT NOT NULL,
  `Module` LONGTEXT,
  `Date` DATETIME,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Payment` (
  `Id` BIGINT NOT NULL,
  `DocumentId` BIGINT NOT NULL,
  `PaymentTypeId` BIGINT NOT NULL,
  `Amount` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `Date` DATE,
  `UserId` BIGINT NOT NULL DEFAULT 0,
  `ZReportId` BIGINT,
  `DateCreated` DATETIME NOT NULL DEFAULT 0,
  `RoundingAdjustment` DOUBLE NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PaymentType` (
  `Id` BIGINT NOT NULL,
  `Name` LONGTEXT NOT NULL,
  `Code` LONGTEXT,
  `IsCustomerRequired` TINYINT NOT NULL DEFAULT 0,
  `IsFiscal` TINYINT NOT NULL DEFAULT 1,
  `IsSlipRequired` TINYINT NOT NULL DEFAULT 0,
  `IsChangeAllowed` TINYINT NOT NULL DEFAULT 1,
  `Ordinal` BIGINT NOT NULL DEFAULT 0,
  `IsEnabled` TINYINT NOT NULL DEFAULT 1,
  `IsQuickPayment` TINYINT NOT NULL DEFAULT 1,
  `OpenCashDrawer` TINYINT NOT NULL DEFAULT 1,
  `ShortcutKey` LONGTEXT,
  `MarkAsPaid` TINYINT NOT NULL DEFAULT 1,
  `RoundingIncrement` DOUBLE NOT NULL DEFAULT 0,
  `RoundingRule` BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PosOrder` (
  `Id` BIGINT NOT NULL,
  `UserId` BIGINT NOT NULL,
  `Number` LONGTEXT NOT NULL,
  `Discount` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `DiscountType` BIGINT NOT NULL DEFAULT 0,
  `Total` DECIMAL(20, 6),
  `CustomerId` BIGINT,
  `ServiceType` BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PosOrderItem` (
  `Id` BIGINT NOT NULL,
  `PosOrderId` BIGINT NOT NULL,
  `ProductId` BIGINT NOT NULL,
  `RoundNumber` BIGINT NOT NULL DEFAULT 0,
  `Quantity` DECIMAL(20, 6) NOT NULL,
  `Price` DECIMAL(20, 6) NOT NULL,
  `IsLocked` TINYINT NOT NULL DEFAULT 0,
  `Discount` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `DiscountType` BIGINT NOT NULL DEFAULT 0,
  `IsFeatured` TINYINT NOT NULL DEFAULT 0,
  `VoidedBy` BIGINT,
  `Comment` LONGTEXT,
  `DateCreated` DATE NOT NULL,
  `Bundle` LONGTEXT,
  `DiscountAppliedType` BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PosPrinterSelection` (
  `Id` BIGINT NOT NULL,
  `Key` VARCHAR(255) NOT NULL,
  `PrinterName` LONGTEXT,
  `IsEnabled` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `PosPrinterSelection_1` (`Key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PosPrinterSelectionSettings` (
  `Id` BIGINT NOT NULL,
  `PosPrinterSelectionId` BIGINT NOT NULL,
  `PaperWidth` BIGINT NOT NULL DEFAULT 32,
  `Header` LONGTEXT,
  `Footer` LONGTEXT,
  `FeedLines` BIGINT NOT NULL DEFAULT 0,
  `CutPaper` TINYINT NOT NULL DEFAULT 1,
  `PrintBitmap` TINYINT NOT NULL DEFAULT 0,
  `OpenCashDrawer` TINYINT NOT NULL DEFAULT 1,
  `CashDrawerCommand` LONGTEXT,
  `HeaderAlignment` TINYINT NOT NULL DEFAULT 0,
  `FooterAlignment` TINYINT NOT NULL DEFAULT 0,
  `IsFormattingEnabled` TINYINT NOT NULL DEFAULT 1,
  `PrinterType` TINYINT NOT NULL DEFAULT 0,
  `NumberOfCopies` TINYINT NOT NULL DEFAULT 1,
  `CodePage` BIGINT NOT NULL DEFAULT -1,
  `CharacterSet` BIGINT NOT NULL DEFAULT -1,
  `Margin` BIGINT NOT NULL DEFAULT 0,
  `LeftMargin` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `TopMargin` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `RightMargin` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `BottomMargin` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `PrintBarcode` TINYINT NOT NULL DEFAULT 1,
  `FontName` LONGTEXT,
  `FontSizePercent` DECIMAL(20, 6) NOT NULL DEFAULT 100.0,
  `PrintLogoFullWidth` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PosPrinterSettings` (
  `Id` BIGINT NOT NULL,
  `PrinterName` VARCHAR(255) NOT NULL,
  `PaperWidth` BIGINT NOT NULL DEFAULT 32,
  `Header` LONGTEXT,
  `Footer` LONGTEXT,
  `FeedLines` BIGINT NOT NULL DEFAULT 0,
  `CutPaper` TINYINT NOT NULL DEFAULT 1,
  `PrintBitmap` TINYINT NOT NULL DEFAULT 0,
  `OpenCashDrawer` TINYINT NOT NULL DEFAULT 1,
  `CashDrawerCommand` LONGTEXT,
  `HeaderAlignment` TINYINT NOT NULL DEFAULT 0,
  `FooterAlignment` TINYINT NOT NULL DEFAULT 0,
  `IsFormattingEnabled` TINYINT NOT NULL DEFAULT 1,
  `PrinterType` TINYINT NOT NULL DEFAULT 0,
  `NumberOfCopies` TINYINT NOT NULL DEFAULT 1,
  `CodePage` BIGINT NOT NULL DEFAULT -1,
  `CharacterSet` BIGINT NOT NULL DEFAULT -1,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `PosPrinterSettings_1` (`PrinterName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PosVoid` (
  `Id` BIGINT NOT NULL,
  `OrderNumber` LONGTEXT NOT NULL,
  `UserId` BIGINT,
  `UserName` LONGTEXT NOT NULL,
  `ProductId` BIGINT,
  `ProductName` LONGTEXT NOT NULL,
  `RoundNumber` BIGINT NOT NULL,
  `Quantity` DECIMAL(20, 6) NOT NULL,
  `Price` DECIMAL(20, 6) NOT NULL,
  `Discount` DECIMAL(20, 6) NOT NULL,
  `DiscountType` BIGINT NOT NULL,
  `Total` DECIMAL(20, 6) NOT NULL,
  `IsConfirmed` BIGINT NOT NULL,
  `Reason` LONGTEXT,
  `VoidedBy` BIGINT,
  `VoidedByName` LONGTEXT,
  `Bundle` LONGTEXT,
  `DateCreated` DATETIME NOT NULL,
  `DateVoided` DATETIME NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Product` (
  `Id` BIGINT NOT NULL,
  `ProductGroupId` BIGINT,
  `Name` LONGTEXT NOT NULL,
  `Code` LONGTEXT,
  `PLU` BIGINT,
  `MeasurementUnit` LONGTEXT,
  `Price` DOUBLE NOT NULL DEFAULT 0,
  `IsTaxInclusivePrice` TINYINT DEFAULT 1,
  `CurrencyId` BIGINT,
  `IsPriceChangeAllowed` TINYINT NOT NULL DEFAULT 0,
  `IsService` TINYINT NOT NULL DEFAULT 0,
  `IsUsingDefaultQuantity` TINYINT NOT NULL DEFAULT 1,
  `IsEnabled` TINYINT NOT NULL DEFAULT 1,
  `Description` LONGTEXT,
  `DateCreated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DateUpdated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Cost` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `Markup` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `Image` LONGBLOB,
  `Color` LONGTEXT NOT NULL DEFAULT 'Transparent',
  `AgeRestriction` DECIMAL(20, 6),
  `LastPurchasePrice` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `Rank` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ProductComment` (
  `Id` BIGINT,
  `ProductId` BIGINT NOT NULL,
  `Comment` LONGTEXT NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ProductGroup` (
  `Id` BIGINT NOT NULL,
  `Name` LONGTEXT NOT NULL,
  `ParentGroupId` BIGINT,
  `Color` LONGTEXT NOT NULL DEFAULT 'Transparent',
  `Image` LONGBLOB,
  `Rank` BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ProductTax` (
  `ProductId` BIGINT NOT NULL,
  `TaxId` BIGINT NOT NULL,
  PRIMARY KEY (`ProductId`, `TaxId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Promotion` (
  `Id` BIGINT NOT NULL,
  `Name` LONGTEXT NOT NULL,
  `StartDate` DATETIME,
  `StartTime` DATETIME,
  `EndDate` DATETIME,
  `EndTime` DATETIME,
  `DaysOfWeek` BIGINT NOT NULL,
  `IsEnabled` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PromotionItem` (
  `Id` BIGINT NOT NULL,
  `PromotionId` BIGINT NOT NULL,
  `Uid` BIGINT NOT NULL,
  `DiscountType` BIGINT NOT NULL DEFAULT 0,
  `PriceType` BIGINT NOT NULL DEFAULT 0,
  `Value` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `IsConditional` TINYINT NOT NULL DEFAULT 1,
  `Quantity` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `ConditionType` BIGINT NOT NULL DEFAULT 0,
  `QuantityLimit` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `SecurityKey` (
  `Name` VARCHAR(255),
  `Level` BIGINT,
  PRIMARY KEY (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `StartingCash` (
  `Id` BIGINT,
  `UserId` BIGINT NOT NULL,
  `Amount` DECIMAL(20, 6) NOT NULL,
  `Description` LONGTEXT,
  `StartingCashType` BIGINT NOT NULL DEFAULT 0,
  `ZReportNumber` BIGINT,
  `DateCreated` DATETIME NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `StartingCash_1` (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Stock` (
  `Id` BIGINT,
  `ProductId` BIGINT NOT NULL,
  `WarehouseId` BIGINT NOT NULL,
  `Quantity` DECIMAL(20, 6) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `StockControl` (
  `Id` BIGINT,
  `ProductId` BIGINT NOT NULL,
  `CustomerId` BIGINT,
  `ReorderPoint` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `PreferredQuantity` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  `IsLowStockWarningEnabled` TINYINT NOT NULL DEFAULT 1,
  `LowStockWarningQuantity` DECIMAL(20, 6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `IX_StockControl_Product` (`ProductId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Tax` (
  `Id` BIGINT NOT NULL,
  `Name` LONGTEXT NOT NULL,
  `Rate` DECIMAL(20, 6) NOT NULL,
  `Code` LONGTEXT,
  `IsFixed` TINYINT NOT NULL DEFAULT 0,
  `IsTaxOnTotal` TINYINT NOT NULL DEFAULT 0,
  `IsEnabled` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Template` (
  `Id` BIGINT NOT NULL,
  `Name` VARCHAR(255) NOT NULL,
  `Value` LONGTEXT NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UK_TemplateName` (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `User` (
  `Id` BIGINT NOT NULL,
  `FirstName` LONGTEXT,
  `LastName` LONGTEXT,
  `Username` LONGTEXT,
  `Password` LONGTEXT NOT NULL,
  `AccessLevel` BIGINT NOT NULL DEFAULT 0,
  `IsEnabled` TINYINT NOT NULL DEFAULT 1,
  `Email` VARCHAR(255),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `UX_UserEmail` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `VoidReason` (
  `Id` BIGINT NOT NULL,
  `Name` LONGTEXT NOT NULL,
  `Rank` BIGINT NOT NULL,
  `DateCreated` DATETIME NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Warehouse` (
  `Id` BIGINT NOT NULL,
  `Name` LONGTEXT NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ZReport` (
  `Id` BIGINT,
  `Number` BIGINT NOT NULL,
  `FromDocumentId` BIGINT NOT NULL,
  `ToDocumentId` BIGINT NOT NULL,
  `DateCreated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache` (
  `key` VARCHAR NOT NULL,
  `value` LONGTEXT NOT NULL,
  `expiration` BIGINT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` VARCHAR NOT NULL,
  `owner` VARCHAR NOT NULL,
  `expiration` BIGINT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` BIGINT NOT NULL,
  `uuid` VARCHAR NOT NULL,
  `connection` VARCHAR NOT NULL,
  `queue` VARCHAR NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` VARCHAR NOT NULL,
  `name` VARCHAR NOT NULL,
  `total_jobs` BIGINT NOT NULL,
  `pending_jobs` BIGINT NOT NULL,
  `failed_jobs` BIGINT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options` LONGTEXT,
  `cancelled_at` BIGINT,
  `created_at` BIGINT NOT NULL,
  `finished_at` BIGINT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jobs` (
  `id` BIGINT NOT NULL,
  `queue` VARCHAR NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` BIGINT NOT NULL,
  `reserved_at` BIGINT,
  `available_at` BIGINT NOT NULL,
  `created_at` BIGINT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `migrations` (
  `id` BIGINT NOT NULL,
  `migration` VARCHAR NOT NULL,
  `batch` BIGINT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` VARCHAR NOT NULL,
  `token` VARCHAR NOT NULL,
  `created_at` DATETIME,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR NOT NULL,
  `user_id` BIGINT,
  `ip_address` VARCHAR,
  `user_agent` LONGTEXT,
  `payload` LONGTEXT NOT NULL,
  `last_activity` BIGINT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT NOT NULL,
  `name` VARCHAR NOT NULL,
  `email` VARCHAR NOT NULL,
  `email_verified_at` DATETIME,
  `password` VARCHAR NOT NULL,
  `remember_token` VARCHAR,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foreign keys are added after all tables so creation order does not matter.
ALTER TABLE `Barcode` ADD CONSTRAINT `fk_Barcode_1_ProductId_Product_Id`
  FOREIGN KEY (`ProductId`)
  REFERENCES `Product` (`Id`)
  ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `Company` ADD CONSTRAINT `fk_Company_2_CountryId_Country_Id`
  FOREIGN KEY (`CountryId`)
  REFERENCES `Country` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `Customer` ADD CONSTRAINT `fk_Customer_3_CountryId_Country_Id`
  FOREIGN KEY (`CountryId`)
  REFERENCES `Country` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `CustomerDiscount` ADD CONSTRAINT `fk_CustomerDiscount_4_CustomerId_Customer_Id`
  FOREIGN KEY (`CustomerId`)
  REFERENCES `Customer` (`Id`)
  ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `Document` ADD CONSTRAINT `fk_Document_5_WarehouseId_Warehouse_Id`
  FOREIGN KEY (`WarehouseId`)
  REFERENCES `Warehouse` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `Document` ADD CONSTRAINT `fk_Document_6_DocumentTypeId_DocumentType_Id`
  FOREIGN KEY (`DocumentTypeId`)
  REFERENCES `DocumentType` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `Document` ADD CONSTRAINT `fk_Document_7_CustomerId_Customer_Id`
  FOREIGN KEY (`CustomerId`)
  REFERENCES `Customer` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `Document` ADD CONSTRAINT `fk_Document_8_UserId_User_Id`
  FOREIGN KEY (`UserId`)
  REFERENCES `User` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `DocumentItem` ADD CONSTRAINT `fk_DocumentItem_9_ProductId_Product_Id`
  FOREIGN KEY (`ProductId`)
  REFERENCES `Product` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `DocumentItem` ADD CONSTRAINT `fk_DocumentItem_10_DocumentId_Document_Id`
  FOREIGN KEY (`DocumentId`)
  REFERENCES `Document` (`Id`)
  ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `DocumentItemExpirationDate` ADD CONSTRAINT `fk_DocumentItemExpirationDate_11_DocumentItemId_DocumentItem_Id`
  FOREIGN KEY (`DocumentItemId`)
  REFERENCES `DocumentItem` (`Id`)
  ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `DocumentItemTax` ADD CONSTRAINT `fk_DocumentItemTax_12_TaxId_Tax_Id`
  FOREIGN KEY (`TaxId`)
  REFERENCES `Tax` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `DocumentItemTax` ADD CONSTRAINT `fk_DocumentItemTax_13_DocumentItemId_DocumentItem_Id`
  FOREIGN KEY (`DocumentItemId`)
  REFERENCES `DocumentItem` (`Id`)
  ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `DocumentType` ADD CONSTRAINT `fk_DocumentType_14_WarehouseId_Warehouse_Id`
  FOREIGN KEY (`WarehouseId`)
  REFERENCES `Warehouse` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `DocumentType` ADD CONSTRAINT `fk_DocumentType_15_DocumentCategoryId_DocumentCategory_Id`
  FOREIGN KEY (`DocumentCategoryId`)
  REFERENCES `DocumentCategory` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `FloorPlanTable` ADD CONSTRAINT `fk_FloorPlanTable_16_FloorPlanId_FloorPlan_Id`
  FOREIGN KEY (`FloorPlanId`)
  REFERENCES `FloorPlan` (`Id`)
  ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `LoyaltyCard` ADD CONSTRAINT `fk_LoyaltyCard_17_CustomerId_Customer_Id`
  FOREIGN KEY (`CustomerId`)
  REFERENCES `Customer` (`Id`)
  ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `Payment` ADD CONSTRAINT `fk_Payment_18_ZReportId_ZReport_Id`
  FOREIGN KEY (`ZReportId`)
  REFERENCES `ZReport` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `Payment` ADD CONSTRAINT `fk_Payment_19_UserId_User_Id`
  FOREIGN KEY (`UserId`)
  REFERENCES `User` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `Payment` ADD CONSTRAINT `fk_Payment_20_PaymentTypeId_PaymentType_Id`
  FOREIGN KEY (`PaymentTypeId`)
  REFERENCES `PaymentType` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `Payment` ADD CONSTRAINT `fk_Payment_21_DocumentId_Document_Id`
  FOREIGN KEY (`DocumentId`)
  REFERENCES `Document` (`Id`)
  ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `PosOrder` ADD CONSTRAINT `fk_PosOrder_22_CustomerId_Customer_Id`
  FOREIGN KEY (`CustomerId`)
  REFERENCES `Customer` (`Id`)
  ON DELETE SET NULL ON UPDATE NO ACTION;

ALTER TABLE `PosOrder` ADD CONSTRAINT `fk_PosOrder_23_UserId_User_Id`
  FOREIGN KEY (`UserId`)
  REFERENCES `User` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `PosOrderItem` ADD CONSTRAINT `fk_PosOrderItem_24_VoidedBy_User_Id`
  FOREIGN KEY (`VoidedBy`)
  REFERENCES `User` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `PosOrderItem` ADD CONSTRAINT `fk_PosOrderItem_25_ProductId_Product_Id`
  FOREIGN KEY (`ProductId`)
  REFERENCES `Product` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `PosOrderItem` ADD CONSTRAINT `fk_PosOrderItem_26_PosOrderId_PosOrder_Id`
  FOREIGN KEY (`PosOrderId`)
  REFERENCES `PosOrder` (`Id`)
  ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `PosPrinterSelectionSettings` ADD CONSTRAINT `fk_PosPrinterSelectionSettings_27_PosPrinterSelectionId_PosPrinterSelection_Id`
  FOREIGN KEY (`PosPrinterSelectionId`)
  REFERENCES `PosPrinterSelection` (`Id`)
  ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `PosVoid` ADD CONSTRAINT `fk_PosVoid_28_VoidedBy_User_Id`
  FOREIGN KEY (`VoidedBy`)
  REFERENCES `User` (`Id`)
  ON DELETE SET NULL ON UPDATE NO ACTION;

ALTER TABLE `PosVoid` ADD CONSTRAINT `fk_PosVoid_29_ProductId_Product_Id`
  FOREIGN KEY (`ProductId`)
  REFERENCES `Product` (`Id`)
  ON DELETE SET NULL ON UPDATE NO ACTION;

ALTER TABLE `PosVoid` ADD CONSTRAINT `fk_PosVoid_30_UserId_User_Id`
  FOREIGN KEY (`UserId`)
  REFERENCES `User` (`Id`)
  ON DELETE SET NULL ON UPDATE NO ACTION;

ALTER TABLE `Product` ADD CONSTRAINT `fk_Product_31_ProductGroupId_ProductGroup_Id`
  FOREIGN KEY (`ProductGroupId`)
  REFERENCES `ProductGroup` (`Id`)
  ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `Product` ADD CONSTRAINT `fk_Product_32_CurrencyId_Currency_Id`
  FOREIGN KEY (`CurrencyId`)
  REFERENCES `Currency` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `Product` ADD CONSTRAINT `fk_Product_33_ProductGroupId_ProductGroup_Id`
  FOREIGN KEY (`ProductGroupId`)
  REFERENCES `ProductGroup` (`Id`)
  ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `ProductComment` ADD CONSTRAINT `fk_ProductComment_34_ProductId_Product_Id`
  FOREIGN KEY (`ProductId`)
  REFERENCES `Product` (`Id`)
  ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `ProductTax` ADD CONSTRAINT `fk_ProductTax_35_TaxId_Tax_Id`
  FOREIGN KEY (`TaxId`)
  REFERENCES `Tax` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `ProductTax` ADD CONSTRAINT `fk_ProductTax_36_ProductId_Product_Id`
  FOREIGN KEY (`ProductId`)
  REFERENCES `Product` (`Id`)
  ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `PromotionItem` ADD CONSTRAINT `fk_PromotionItem_37_PromotionId_Promotion_Id`
  FOREIGN KEY (`PromotionId`)
  REFERENCES `Promotion` (`Id`)
  ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `StartingCash` ADD CONSTRAINT `fk_StartingCash_38_UserId_User_Id`
  FOREIGN KEY (`UserId`)
  REFERENCES `User` (`Id`)
  ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `Stock` ADD CONSTRAINT `fk_Stock_39_WarehouseId_Warehouse_Id`
  FOREIGN KEY (`WarehouseId`)
  REFERENCES `Warehouse` (`Id`)
  ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `Stock` ADD CONSTRAINT `fk_Stock_40_ProductId_Product_Id`
  FOREIGN KEY (`ProductId`)
  REFERENCES `Product` (`Id`)
  ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `StockControl` ADD CONSTRAINT `fk_StockControl_41_CustomerId_Customer_Id`
  FOREIGN KEY (`CustomerId`)
  REFERENCES `Customer` (`Id`)
  ON DELETE SET NULL ON UPDATE NO ACTION;

ALTER TABLE `StockControl` ADD CONSTRAINT `fk_StockControl_42_ProductId_Product_Id`
  FOREIGN KEY (`ProductId`)
  REFERENCES `Product` (`Id`)
  ON DELETE CASCADE ON UPDATE NO ACTION;

SET FOREIGN_KEY_CHECKS = 1;

