local state = require 'queue.abstract.state'

local tube = {}
local method = {}

local i_status = 2

-- create space
function tube.create_space(space_name, opts)
    local space_opts = {}
    if opts.temporary then
        space_opts.temporary = true
    end

    -- id, status, utube, data
    local space = box.schema.create_space(space_name, space_opts)
    space:create_index('task_id', { type = 'tree', parts = { 1, 'num' }})
    space:create_index('status',
        { type = 'tree', parts = { 2, 'str', 1, 'num' }})
    space:create_index('utube',
        { type = 'tree', parts = { 2, 'str', 3, 'str', 1, 'num' }})
    return space
end

-- start tube on space
function tube.new(space, on_task_change)
    on_task_change = on_task_change or (function() end)
    local self = setmetatable({
        space          = space,
        on_task_change = on_task_change,
    }, { __index = method })
    return self
end

-- normalize task: cleanup all internal fields
function method.normalize_task(self, task)
    return task and task:transform(3, 1)
end

-- put task in space
function method.put(self, data, opts)
    local max = self.space.index.task_id:max()
    local id = max and max[1] + 1 or 0
    local task = self.space:insert{id, state.READY, tostring(opts.utube), data}
    self.on_task_change(task, 'put')
    return task
end

-- take task
function method.take(self)
    for s, task in self.space.index.status:pairs(state.READY,
        { iterator = 'GE' }) do
        if task[2] ~= state.READY then
            break
        end

        local taken = self.space.index.utube:min{state.TAKEN, task[3]}
        if taken == nil or taken[2] ~= state.TAKEN then
            task = self.space:update(task[1], { { '=', 2, state.TAKEN } })
            self.on_task_change(task, 'take')
            return task
        end
    end
end

-- delete task
function method.delete(self, id)
    local task = self.space:delete(id)
    if task ~= nil then
        task = task:transform(2, 1, state.DONE)

        local neighbour = self.space.index.utube:min{state.READY, task[3]}
        self.on_task_change(task, 'delete')
        if neighbour then
            self.on_task_change(neighbour)
        end
    else
        self.on_task_change(task, 'delete')
    end
    return task
end

-- release task
function method.release(self, id, opts)
    local task = self.space:update(id, {{ '=', 2, state.READY }})
    if task ~= nil then
        self.on_task_change(task, 'release')
    end
    return task
end

-- bury task
function method.bury(self, id)
    local task = self.space:update(id, {{ '=', 2, state.BURIED }})
    if task ~= nil then
        local neighbour = self.space.index.utube:min{state.READY, task[3]}
        self.on_task_change(task, 'bury')
        if neighbour and neighbour[i_status] == state.READY then
            self.on_task_change(neighbour)
        end
    else
        self.on_task_change(task, 'bury')
    end
    return task
end

-- unbury several tasks
function method.kick(self, count)
    for i = 1, count do
        local task = self.space.index.status:min{ state.BURIED }
        if task == nil then
            return i - 1
        end
        if task[2] ~= state.BURIED then
            return i - 1
        end

        task = self.space:update(task[1], {{ '=', 2, state.READY }})
        self.on_task_change(task, 'kick')
    end
    return count
end

-- peek task
function method.peek(self, id)
    return self.space:get{id}
end

return tube
