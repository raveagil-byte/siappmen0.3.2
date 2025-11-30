<div class="units">
    <h1>Units</h1>
    <button @click="showCreateModal = true">Create Unit</button>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Code</th>
          <th>Name</th>
          <th>Location</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="unit in units" :key="unit.id">
          <td>{{ unit.id }}</td>
          <td>{{ unit.code }}</td>
          <td>{{ unit.name }}</td>
          <td>{{ unit.location }}</td>
          <td>{{ unit.is_active ? 'Active' : 'Inactive' }}</td>
          <td>
            <button @click="editUnit(unit)">Edit</button>
            <button @click="deleteUnit(unit.id)">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || editingUnit" class="modal">
      <div class="modal-content">
        <h2>{{ editingUnit ? 'Edit Unit' : 'Create Unit' }}</h2>
        <form @submit.prevent="saveUnit">
          <input v-model="form.code" placeholder="Code" required />
          <input v-model="form.name" placeholder="Name" required />
          <input v-model="form.location" placeholder="Location" />
          <textarea v-model="form.description" placeholder="Description"></textarea>
          <label>
            <input type="checkbox" v-model="form.is_active" />
            Active
          </label>
          <button type="submit">{{ editingUnit ? 'Update' : 'Create' }}</button>
          <button type="button" @click="closeModal">Cancel</button>
        </form>
      </div>
    </div>
  </div>
</template>
=======
<template>
  <div class="units">
    <h1>Units</h1>
    <button @click="showCreateModal = true">Create Unit</button>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Code</th>
          <th>Name</th>
          <th>Location</th>
          <th>Status</th>
          <th>QR Code</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="unit in units" :key="unit.id">
          <td>{{ unit.id }}</td>
          <td>{{ unit.code }}</td>
          <td>{{ unit.name }}</td>
          <td>{{ unit.location }}</td>
          <td>{{ unit.is_active ? 'Active' : 'Inactive' }}</td>
          <td>
            <QRDisplay :content="`UNIT:${unit.uuid}`" :size="100" />
          </td>
          <td>
            <button @click="editUnit(unit)">Edit</button>
            <button @click="deleteUnit(unit.id)">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || editingUnit" class="modal">
      <div class="modal-content">
        <h2>{{ editingUnit ? 'Edit Unit' : 'Create Unit' }}</h2>
        <form @submit.prevent="saveUnit">
          <input v-model="form.code" placeholder="Code" required />
          <input v-model="form.name" placeholder="Name" required />
          <input v-model="form.location" placeholder="Location" />
          <textarea v-model="form.description" placeholder="Description"></textarea>
          <label>
            <input type="checkbox" v-model="form.is_active" />
            Active
          </label>
          <button type="submit">{{ editingUnit ? 'Update' : 'Create' }}</button>
          <button type="button" @click="closeModal">Cancel</button>
        </form>
      </div>
    </div>
  </div>
</template>
