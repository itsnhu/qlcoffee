terraform {

  backend "azurerm" {

    resource_group_name  = "rg-qlcoffee-demo"

    storage_account_name = "stqlcoffee"

    container_name       = "tfstate"

    key                  = "terraform.tfstate"

  }

}