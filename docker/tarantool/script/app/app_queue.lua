queue = require('queue')
queue.start()

function init_tube(tube_name, type)
    if not queue.tube[tube_name] then
        queue.create_tube(tube_name, type)
    end
end

state = {
    READY = 'r',
    TAKEN = 't',
    DONE = '-',
    BURIED = '!',
    DELAYED = '~',
}

queue_list = {}

function set_spaces()
    for _, space in pairs(box.space) do
        queue_list[space['name']] = space['name']
    end
end


local human_states = {}

human_states[state.READY] = 'ready'
human_states[state.TAKEN] = 'taken'
human_states[state.DONE] = 'delayed'
human_states[state.BURIED] = 'buried'
human_states[state.DELAYED] = 'done'

function sortpairs(source_table)
    local u = { }

    for k, v in pairsByKeys(source_table) do
        table.insert(u, k )
    end

    return u
end

function pairsByKeys (t, f)
    local a = {}
    for n in pairs(t) do
        table.insert(a, n)
    end
    table.sort(a, f)
    local i = 0      -- iterator variable
    local iter = function ()   -- iterator function
        i = i + 1
        if a[i] == nil then
            return nil
        else
            return a[i] .. ' : ' .. t[a[i]]
        end
    end
    return iter
end

function stats()
    set_spaces()

    local result = {}

    for _, name in pairs(queue_list) do
        if string.sub(name, 1, 1) ~= '_' then
            result[name] = get_ready_taken_counts(box.space[name])
        end
    end

    result = sortpairs(result)

    return result

end

function get_counts(space)
    return {
        ready = space.index[1]:count(state.READY),
        taken = space.index[1]:count(state.TAKEN),
        --        done = space.index[1]:count(state.DONE),
        --        buried = space.index[1]:count(state.BURIED),
        delayed = space.index[1]:count(state.DELAYED),
        --        total = space:len()
    }
end

function get_ready_taken_counts(space)
    return tostring(space.index[1]:count(state.READY)) .. ' : ' .. tostring(space.index[1]:count(state.TAKEN)) .. ' : ' .. tostring(space.index[1]:count(state.DELAYED))
end
