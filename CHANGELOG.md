# Nova Sound FX - Actualización v1.1.0

## Nuevas Características

### 1. Opciones de Visualización por Mapeo

Ahora cada mapeo CSS puede tener configuraciones individuales para:

- **Mostrar efecto visual**: Controla si se muestra la animación visual cuando se reproduce el sonido
- **Mostrar icono de bocina**: Controla si se muestra el icono 🔊 al interactuar con el elemento

### 2. Prevención de Conflictos CSS

Los cambios realizados previenen conflictos con estilos existentes:

- No se agregan transiciones CSS automáticas a elementos con sonido
- No se modifica el cursor automáticamente
- Los efectos visuales solo se aplican cuando están explícitamente habilitados
- Se eliminaron las reglas de `pointer-events` que podían interferir con menús

### 3. Mejoras en el Panel de Administración

- Los mapeos CSS ahora se cargan correctamente y son visibles
- Nuevas opciones en el formulario de mapeo
- Mejor manejo de errores y mensajes de depuración

## Cómo Usar las Nuevas Opciones

### En el Panel de Administración

1. Ve a **Nova Sound FX** > **Mapeo de Sonidos CSS**
2. Al crear o editar un mapeo, encontrarás las nuevas opciones:
   - ✓ Mostrar efecto visual al reproducir sonido
   - ✓ Mostrar icono de bocina

### Estilos CSS Personalizados

Si deseas agregar tus propios estilos a elementos con sonido, puedes usar estas clases:

```css
/* Elemento con sonido */
.nova-sound-fx-active {
    /* tus estilos aquí */
}

/* Elemento con efecto visual habilitado */
.nova-sound-fx-active.nova-visual-enabled {
    /* tus estilos aquí */
}

/* Elemento con icono de bocina habilitado */
.nova-sound-fx-active.nova-speaker-enabled {
    /* tus estilos aquí */
}

/* Elemento mientras reproduce sonido (solo si visual está habilitado) */
.nova-sound-fx-active.nova-visual-enabled.nova-sound-playing {
    /* tus estilos aquí */
}
```

## Migración de Datos

Si ya tenías mapeos configurados, estos se actualizarán automáticamente con las opciones visuales habilitadas por defecto.

## Solución de Problemas

### Los mapeos no se muestran

1. Limpia la caché del navegador
2. Revisa la consola del navegador para errores
3. Asegúrate de que el plugin esté activo

### Los efectos hover de mi menú no funcionan

Los nuevos cambios evitan conflictos con estilos existentes. Si aún tienes problemas:

1. Desactiva las opciones visuales para ese mapeo específico
2. Agrega estilos CSS personalizados con mayor especificidad

## Registro de Cambios

### v1.1.0
- Agregadas opciones de visualización por mapeo
- Corregido problema de mapeos CSS no visibles
- Mejorada compatibilidad con estilos de temas
- Agregado sistema de migraciones para actualizaciones futuras
