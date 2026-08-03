resource "azurerm_kubernetes_cluster" "aks" {

  name                = var.aks_name
  location            = azurerm_resource_group.demo.location
  resource_group_name = azurerm_resource_group.demo.name

  dns_prefix = "qlcoffee"

  default_node_pool {
    name       = "default"
    node_count = 1
    vm_size    = "Standard_B2as_v2"
  }
  

  identity {
    type = "SystemAssigned"
  }

  tags = {
    Environment = "Demo-GiangVien"
    Owner       = "QLCoffee"
  }
}

resource "azurerm_role_assignment" "acr_pull" {

  principal_id         = azurerm_kubernetes_cluster.aks.kubelet_identity[0].object_id
  role_definition_name = "AcrPull"
  scope                = azurerm_container_registry.acr.id

}

