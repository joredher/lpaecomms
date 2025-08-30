-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8 ;
USE `mydb` ;

-- -----------------------------------------------------
-- Table `mydb`.`lpa_category`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`lpa_category` (
  `lpa_category_ID` INT NOT NULL AUTO_INCREMENT,
  `lpa_category_name` VARCHAR(45) NOT NULL,
  `lpa_category_desc` TEXT NULL,
  PRIMARY KEY (`lpa_category_ID`),
  UNIQUE INDEX `lpa_category_name_UNIQUE` (`lpa_category_name` ASC) VISIBLE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`lpa_type`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`lpa_type` (
  `lpa_type_ID` INT NOT NULL AUTO_INCREMENT,
  `lpa_type_name` VARCHAR(45) NOT NULL,
  `lpa_type_desc` TEXT NULL,
  PRIMARY KEY (`lpa_type_ID`),
  UNIQUE INDEX `lpa_type_name_UNIQUE` (`lpa_type_name` ASC) VISIBLE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`lpa_stock`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`lpa_stock` (
  `lpa_stock_ID` BIGINT(20) NOT NULL,
  `lpa_stock_name` VARCHAR(250) NOT NULL,
  `lpa_stock_desc` TEXT NULL,
  `lpa_stock_onhand` VARCHAR(5) NULL,
  `lpa_stock_price` DECIMAL(7,2) NULL DEFAULT 0,
  `lpa_stock_status` CHAR(1) NULL DEFAULT 'P',
  `lpa_stock_publish_at` DATETIME NULL,
  `lpa_fk_category_ID` INT NOT NULL,
  `lpa_fk_type_ID` INT NOT NULL,
  `lpa_invitem_inv_no` VARCHAR(20) NOT NULL,
  PRIMARY KEY (`lpa_stock_ID`),
  INDEX `fk_lpa_stock_lpa_category_idx` (`lpa_fk_category_ID` ASC) VISIBLE,
  INDEX `fk_lpa_stock_lpa_type1_idx` (`lpa_fk_type_ID` ASC) VISIBLE,
  CONSTRAINT `fk_lpa_stock_lpa_category`
    FOREIGN KEY (`lpa_fk_category_ID`)
    REFERENCES `mydb`.`lpa_category` (`lpa_category_ID`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_lpa_stock_lpa_type1`
    FOREIGN KEY (`lpa_fk_type_ID`)
    REFERENCES `mydb`.`lpa_type` (`lpa_type_ID`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`lpa_clients`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`lpa_clients` (
  `lpa_clients_ID` INT NOT NULL AUTO_INCREMENT,
  `lpa_clients_firstname` VARCHAR(50) NOT NULL,
  `lpa_clients_lastname` VARCHAR(50) CHARACTER SET 'armscii8' NOT NULL,
  `lpa_client_address` VARCHAR(250) NOT NULL,
  `lpa_client_phone` INT(15) NOT NULL,
  `lpa_client_email` VARCHAR(500) NOT NULL,
  `lpa_client_status` CHAR NULL DEFAULT 'A',
  PRIMARY KEY (`lpa_clients_ID`),
  UNIQUE INDEX `lpa_client_email_UNIQUE` (`lpa_client_email` ASC) VISIBLE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`lpa_invoices`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`lpa_invoices` (
  `lpa_invoices_ID` INT NOT NULL AUTO_INCREMENT,
  `lpa_inv_no` VARCHAR(20) NOT NULL,
  `lpa_inv_date` DATETIME NOT NULL,
  `lpa_fk_clients_ID` INT NOT NULL,
  `lpa_inv_client_name` VARCHAR(50) NOT NULL,
  `lpa_inv_client_address` VARCHAR(250) NOT NULL,
  `lpa_inv_amount` DECIMAL(8,2) NOT NULL DEFAULT 0,
  `lpa_inv_status` CHAR(1) NULL DEFAULT 'A',
  PRIMARY KEY (`lpa_invoices_ID`),
  UNIQUE INDEX `lpa_inv_no_UNIQUE` (`lpa_inv_no` ASC) VISIBLE,
  INDEX `fk_lpa_invoices_lpa_clients1_idx` (`lpa_fk_clients_ID` ASC) VISIBLE,
  CONSTRAINT `fk_lpa_invoices_lpa_clients1`
    FOREIGN KEY (`lpa_fk_clients_ID`)
    REFERENCES `mydb`.`lpa_clients` (`lpa_clients_ID`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`lpa_invoice_items`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`lpa_invoice_items` (
  `lpa_invoice_items_ID` INT NOT NULL AUTO_INCREMENT,
  `lpa_fk_invoices_ID` INT NOT NULL,
  `lpa_invitem_stock_name` VARCHAR(250) NOT NULL,
  `lpa_fk_stock_ID` BIGINT(20) NOT NULL,
  `lpa_invitem_qty` INT(15) NOT NULL DEFAULT 1,
  `lpa_invitem_stock_price` DECIMAL(7,2) NOT NULL DEFAULT 0,
  `lpa_invitem_stock_amount` DECIMAL(7,2) NOT NULL DEFAULT 0,
  `lpa_inv_status` CHAR(1) NULL DEFAULT 'A',
  PRIMARY KEY (`lpa_invoice_items_ID`),
  INDEX `fk_lpa_invoice_items_lpa_invoices1_idx` (`lpa_fk_invoices_ID` ASC) VISIBLE,
  INDEX `fk_lpa_invoice_items_lpa_stock1_idx` (`lpa_fk_stock_ID` ASC) VISIBLE,
  CONSTRAINT `fk_lpa_invoice_items_lpa_invoices1`
    FOREIGN KEY (`lpa_fk_invoices_ID`)
    REFERENCES `mydb`.`lpa_invoices` (`lpa_invoices_ID`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_lpa_invoice_items_lpa_stock1`
    FOREIGN KEY (`lpa_fk_stock_ID`)
    REFERENCES `mydb`.`lpa_stock` (`lpa_stock_ID`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`lpa_user_group`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`lpa_user_group` (
  `lpa_user_group_ID` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`lpa_user_group_ID`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC) VISIBLE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`lpa_users`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`lpa_users` (
  `lpa_users_ID` INT NOT NULL AUTO_INCREMENT,
  `lpa_user_username` VARCHAR(30) NOT NULL,
  `lpa_user_password` VARCHAR(50) NOT NULL,
  `lpa_user_firstname` VARCHAR(50) NOT NULL,
  `lpa_user_lastname` VARCHAR(50) NOT NULL,
  `lpa_fk_user_group_ID` INT NOT NULL,
  `lpa_user_status` CHAR(1) NULL DEFAULT 'A',
  PRIMARY KEY (`lpa_users_ID`),
  UNIQUE INDEX `lpa_user_username_UNIQUE` (`lpa_user_username` ASC) VISIBLE,
  INDEX `fk_lpa_users_lpa_user_group1_idx` (`lpa_fk_user_group_ID` ASC) VISIBLE,
  CONSTRAINT `fk_lpa_users_lpa_user_group1`
    FOREIGN KEY (`lpa_fk_user_group_ID`)
    REFERENCES `mydb`.`lpa_user_group` (`lpa_user_group_ID`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
