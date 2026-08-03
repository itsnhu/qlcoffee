resource "azurerm_storage_account" "tfstate" {

  name                = "stqlcoffee"
  resource_group_name = azurerm_resource_group.demo.name
  location            = azurerm_resource_group.demo.location

  account_tier             = "Standard"
  account_replication_type = "LRS"

}

resource "azurerm_storage_container" "tfstate" {

  name                  = "tfstate"
  storage_account_id    = azurerm_storage_account.tfstate.id
  container_access_type = "private"

}